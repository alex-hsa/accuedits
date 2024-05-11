<?php

declare(strict_types=1);

namespace Drupal\openai;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use OpenAI\Client;
use OpenAI\Exceptions\TransporterException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The OpenAI API wrapper class for interacting with the client.
 */
class OpenAIApi implements ContainerInjectionInterface {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The OpenAI API constructor.
   *
   * @param \OpenAI\Client $client
   *   The OpenAI HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory service.
   */
  public function __construct(Client $client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->logger = $loggerChannelFactory->get('openai');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai.client'),
      $container->get('cache.default'),
      $container->get('logger'),
    );
  }

  /**
   * Obtains a list of models from OpenAI and caches the result.
   *
   * This method does its best job to filter out deprecated or unused models. The
   * OpenAI API endpoint does not have a way to filter those out yet.
   *
   * @return array
   *   A filtered list of public models.
   */
  public function getModels(): array {
    $models = [];

    $cache_data = $this->cache->get('openai_models', $models);

    if (!empty($cache_data)) {
      return $cache_data->data;
    }

    $list = $this->client->models()->list()->toArray();

    foreach ($list['data'] as $model) {
      if ($model['owned_by'] === 'openai-dev') {
        continue;
      }

      if (!preg_match('/^(gpt|text|tts|whisper|dall-e)/i', $model['id'])) {
        continue;
      }

      // Skip unused. hidden, or deprecated models.
      if (preg_match('/(search|similarity|edit|1p|instruct|embed)/i', $model['id'])) {
        continue;
      }

      if (in_array($model['id'], ['tts-1-hd-1106', 'tts-1-1106'])) {
        continue;
      }

      $models[$model['id']] = $model['id'];
    }

    if (!empty($models)) {
      asort($models);
      $this->cache->set('openai_models', $models);
    }

    return $models;
  }

  /**
   * Filter specific models from the list of models.
   *
   * @param array $model_type
   *   The type of the model, gpt, text, dall, tts, whisper.
   *
   * @return array
   *   The filtered models.
   */
  public function filterModels(array $model_type): array {
    $models = [];
    $types = implode('|', $model_type);

    foreach ($this->getModels() as $id => $model) {
      if (preg_match("/^({$types})/i", $model)) {
        $models[$id] = $model;
      }
    }

    return $models;
  }

  /**
   * Get the latest embedding model.
   *
   * @return string
   *   The embedding model in OpenAI.
   */
  public function embeddingModel(): string {
    return 'text-embedding-ada-002';
  }

  /**
   * Return a ready to use answer from the completion endpoint.
   *
   * Note that the stream argument will not work in cases like Drupal's Form API
   * AJAX responses at this time. It will however work in client side applications, such as
   * the openai_ckeditor module.
   *
   * @param string $model
   *   The model to use.
   * @param string $prompt
   *   The prompt to use.
   * @param $temperature
   *   The temperature setting.
   * @param $max_tokens
   *   The max tokens for the input and response.
   * @param bool $stream
   *   If the response should be streamed. Useful for dynamic typed output over JavaScript,
   *   see the openai_ckeditor module.
   *
   * @return string
   */
  public function completions(string $model, string $prompt, $temperature, $max_tokens = 512, bool $stream_response = FALSE) {
    try {
      if ($stream_response) {
        $stream = $this->client->completions()->createStreamed(
          [
            'model' => $model,
            'prompt' => trim($prompt),
            'temperature' => (int) $temperature,
            'max_tokens' => (int) $max_tokens,
          ]
        );

        return new StreamedResponse(function () use ($stream) {
          foreach ($stream as $data) {
            echo $data->choices[0]->delta->content;
            ob_flush();
            flush();
          }
        }, 200, [
          'Cache-Control' => 'no-cache, must-revalidate',
          'Content-Type' => 'text/event-stream',
          'X-Accel-Buffering' => 'no',
        ]);
      } else {
        $response = $this->client->completions()->create(
          [
            'model' => $model,
            'prompt' => trim($prompt),
            'temperature' => (int) $temperature,
            'max_tokens' => (int) $max_tokens,
          ],
        );

        $result = $response->toArray();
        return trim($result['choices'][0]['text']);
      }
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * Return a ready to use answer from the chat endpoint.
   *
   * @param string $model
   *   The model to use.
   * @param array $messages
   *   The array of messages to send. Refer to the docs for the format of this array.
   * @param $temperature
   *   The temperature setting.
   * @param $max_tokens
   *   The max tokens for the input and response.
   * @param bool $stream_response
   *   If the response should be streamed. Useful for dynamic typed output over JavaScript,
   *   see the openai_ckeditor module.
   * @param int $seed
   *   If specified, a request with same seed and parameters should return
   *   the same result.
   *
   * @return string|\Symfony\Component\HttpFoundation\StreamedResponse
   *   The response from OpenAI.
   */
  public function chat(string $model, array $messages, $temperature, $max_tokens = 512, bool $stream_response = FALSE, int $seed = NULL) {
    try {
      if ($stream_response) {
        $stream = $this->client->chat()->createStreamed(
          [
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'max_tokens' => (int) $max_tokens,
            'seed' => $seed,
          ]
        );

        return new StreamedResponse(function () use ($stream) {
          foreach ($stream as $data) {
            echo $data->choices[0]->delta->content;
            ob_flush();
            flush();
          }
        }, 200, [
          'Cache-Control' => 'no-cache, must-revalidate',
          'Content-Type' => 'text/event-stream',
          'X-Accel-Buffering' => 'no',
        ]);
      }
      else {
        $response = $this->client->chat()->create(
          [
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'max_tokens' => (int) $max_tokens,
            'seed' => $seed,
          ]
        );

        $result = $response->toArray();
        return trim($result['choices'][0]['message']['content']);
      }
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * Return a ready to use answer from the image endpoint.
   *
   * @param string $model
   *   The model to use.
   * @param string $prompt
   *   The prompt to use.
   * @param string $size
   *   The size image to generate.
   * @param string $response_format
   *   The response format to return, either url or b64_json.
   * @param string $quality
   *   The quality of the image.
   * @param string $style
   *   The style of the image.
   *
   * @return string
   *   The response from OpenAI.
   */
  public function images(string $model, string $prompt, string $size, string $response_format, string $quality = 'standard', string $style = 'natural') {
    try {
      $parameters = [
        'prompt' => $prompt,
        'model' => $model,
        'size' => $size,
        'response_format' => $response_format,
      ];

      if ($model === 'dall-e-3') {
        $parameters['quality'] = $quality;
        $parameters['style'] = $style;
      }

      $response = $this->client->images()->create($parameters);
      $response = $response->toArray();
      return $response['data'][0][$response_format];
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * Return a ready to use answer from the speech endpoint.
   *
   * @param string $model
   *   The model to use.
   * @param string $input
   *   The text input to convert.
   * @param string $voice
   *   The "voice" to use for the audio.
   * @param string $response_format
   *   The audio format to return.
   *
   * @return string
   *   The response from OpenAI.
   */
  public function textToSpeech(string $model, string $input, string $voice, string $response_format) {
    try {
      return $this->client->audio()->speech([
        'model' => $model,
        'voice' => $voice,
        'input' => $input,
        'response_format' => $response_format,
      ]);
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * Return a ready to use transcription/translation from the speech endpoint.
   *
   * @param string $model
   *   The model to use.
   * @param string $file
   *   The absolute path to the audio file to convert.
   * @param string $task
   *   The type of conversion to perform, either transcript or translate.
   * @param string $response_format
   *   The format of the transcript output, in one of these options: json, text, srt, verbose_json, or vtt.
   *
   * @return string
   *   The response from OpenAI.
   */
  public function speechToText(string $model, string $file, string $task = 'transcribe', $temperature = 0.4, string $response_format = 'verbose_json') {
    if (!in_array($task, ['transcribe', 'translate'])) {
      throw new \InvalidArgumentException('The $task parameter must be one of transcribe or translate.');
    }

    try {
      $response = $this->client->audio()->$task([
        'model' => $model,
        'file' => fopen($file, 'r'),
        'temperature' => (int) $temperature,
        'response_format' => $response_format,
      ]);

      $result = $response->toArray();
      return $result['text'];
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * Determine if a piece of text violates any OpenAI usage policies.
   *
   * @param string $input
   *   The input to check.
   *
   * @return bool
   *   Whether this text violates OpenAI's usage policies.
   */
  public function moderation(string $input): bool {
    try {
      $response = $this->client->moderations()->create(
        [
          'model' => 'text-moderation-latest',
          'input' => trim($input),
        ],
      );

      $result = $response->toArray();
      return (bool) $result["results"][0]["flagged"];
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Generate a text embedding from an input.
   *
   * @param string $input
   *   The input to check.
   *
   * @return array
   *   The text embedding vector value from OpenAI.
   */
  public function embedding(string $input): array {
    try {
      $response = $this->client->embeddings()->create([
        'model' => 'text-embedding-ada-002',
        'input' => $input,
      ]);

      $result = $response->toArray();

      return $result['data'][0]['embedding'];
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
