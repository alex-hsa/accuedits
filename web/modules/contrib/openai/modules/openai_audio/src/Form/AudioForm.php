<?php

declare(strict_types=1);

namespace Drupal\openai_audio\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to interact with the OpenAI API's audio (speech to text)
 * endpoints.
 */
class AudioForm extends FormBase {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_audio_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->api = $container->get('openai.api');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['audio'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Audio file path'),
      '#rows' => 1,
      '#description' => $this->t('The absolute path to the audio file. Maximum file size 25 MB. Allowed file types: mp3, mp4, mpeg, mpga, m4a, wav, and webm.'),
      '#required' => TRUE,
    ];

    $form['task'] = [
      '#type' => 'select',
      '#title' => $this->t('Task'),
      '#options' => [
        'transcribe' => 'Transcribe',
        'translate' => 'Translate',
      ],
      '#default_value' => 'transcribe',
      '#description' => $this->t('The task to use to process the audio file. "Transcribe": transcribes the audio to the same language as the audio. "Translate": translates and transcribes the audio into English. See the <a href=":link">speech to text guide</a> for further details.', [':link' => 'https://platform.openai.com/docs/guides/speech-to-text']),
    ];

    $form['response'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Response'),
      '#attributes' =>
        [
          'readonly' => 'readonly',
        ],
      '#prefix' => '<div id="openai-audio-response">',
      '#suffix' => '</div>',
      '#description' => $this->t('The response from OpenAI will appear in the textarea above.')
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-audio-response',
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
  public function validateForm(array &$form, FormStateInterface $form_state) {}

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
    $form['response']['#value'] = $storage['text'];
    return $form['response'];
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
    $audio = $form_state->getValue('audio');
    $task = $form_state->getValue('task');

    $result = $this->api->speechToText(
      'whisper-1',
      $audio,
      $task
    );

    $form_state->setStorage(['text' => $result]);
    $form_state->setRebuild();
  }
}
