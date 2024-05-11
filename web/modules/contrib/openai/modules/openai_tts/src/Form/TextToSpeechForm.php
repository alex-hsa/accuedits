<?php

declare(strict_types=1);

namespace Drupal\openai_tts\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to interact with the OpenAI API's tts (text to speech)
 * endpoints.
 */
class TextToSpeechForm extends FormBase {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The datetime service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The file generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_tts_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->api = $container->get('openai.api');
    $instance->fileSystem = $container->get('file_system');
    $instance->time = $container->get('datetime.time');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to convert'),
      '#description' => $this->t('The text to turn into speech.'),
      '#required' => TRUE,
    ];

    $models = $this->api->filterModels(['tts']);

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $models,
      '#default_value' => 'tts-1',
      '#description' => $this->t('The model to use to turn text into speech. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/models/tts']),
    ];

    $form['voice'] = [
      '#type' => 'select',
      '#title' => $this->t('Voice'),
      '#options' => [
        'alloy' => 'Alloy',
        'echo' => 'Echo',
        'fable' => 'Fable',
        'onyx' => 'Onyx',
        'nova' => 'Nova',
        'shimmer' => 'Shimmer',
      ],
      '#default_value' => 'alloy',
      '#description' => $this->t('The voice to use to turn text into speech. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/guides/text-to-speech/voice-options']),
    ];

    $form['response_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Response Format'),
      '#options' => [
        'mp3' => 'MP3',
        'opus' => 'Opus',
        'aac' => 'AAC',
        'flac' => 'FLAC',
      ],
      '#default_value' => 'mp3',
      '#description' => $this->t('The audio format of the result. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/guides/text-to-speech/supported-output-formats']),
    ];

    $form['response'] = [
      '#markup' => 'The response will create a file link to the audio file below.',
    ];

    $form['file'] = [
      '#prefix' => '<div id="openai-tts-response">',
      '#suffix' => '</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-tts-response',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $text = $form_state->getValue('text');

    if (mb_strlen($text) > 4096) {
      $form_state->setErrorByName('text', 'The input cannot exceed 4096 characters.');
    }
  }

  /**
   * Renders the response.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form element.
   */
  public function getResponse(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $file_url = $this->fileUrlGenerator->generate($storage['filepath']);
    $link = Link::fromTextAndUrl($storage['filename'], $file_url)->toString();
    $form['file']['#markup'] = 'Download the file: ' . $link;
    return $form['file'];
  }

  /**
   * Submits the input to OpenAI.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $text = $form_state->getValue('text');
    $model = $form_state->getValue('model');
    $voice = $form_state->getValue('voice');
    $format = $form_state->getValue('response_format');

    try {
      $response = $this->api->textToSpeech($model, $text, $voice, $format);
      $filename = 'tts_result-' . $this->time->getCurrentTime() . '.' . $format;
      $file_uri = $this->fileSystem->saveData($response, 'public://' . $filename, FileSystemInterface::EXISTS_REPLACE);
      $file = File::create(['uri' => $file_uri]);
      $file->setOwnerId($this->currentUser()->id());
      $file->setPermanent();
      $file->save();

      $form_state->setStorage(
        [
          'filepath' => $file->getFileUri(),
          'fid' => $file->id(),
          'filename' => $file->getFilename(),
        ],
      );
    } catch (\Exception $e) {

    }

    $form_state->setRebuild();
  }
}
