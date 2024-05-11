<?php

namespace Drupal\openai_ckeditor\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\openai\OpenAIApi;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for CKEditor integration routes.
 */
class Completion implements ContainerInjectionInterface {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * The Completion controller constructor.
   *
   * @param \Drupal\openai\OpenAIApi $api
   *   The OpenAI API wrapper.
   */
  public function __construct(OpenAIApi $api) {
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai.api')
    );
  }

  /**
   * Builds the response.
   */
  public function generate(Request $request) {
    $data = json_decode($request->getContent());
    $use_chat_endpoint = str_contains($data->options->model, 'gpt');

    if (!$use_chat_endpoint) {
      return $this->api->completions($data->options->model, trim($data->prompt), floatval($data->options->temperature), (int) $data->options->max_tokens, TRUE);
    } else {
      $messages = [
        ['role' => 'system', 'content' => 'You are an expert in content editing and an assistant to a user writing content for their website. Please return all answers without using first, second, or third person voice.'],
        ['role' => 'user', 'content' => trim($data->prompt)]
      ];

      return $this->api->chat($data->options->model, $messages, floatval($data->options->temperature), (int) $data->options->max_tokens, TRUE);
    }
  }

}
