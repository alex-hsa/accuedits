<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the OpenAI openai_eca_execute_speech action.
 *
 * @Action(
 *   id = "openai_eca_execute_speech",
 *   label = @Translation("OpenAI/ChatGPT Speech to Text"),
 *   description = @Translation("Process an audio file through the OpenAI speech to text endpoint.")
 * )
 */
class SpeechToText extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'model' => 'tts-1',
      'voice' => 'alloy',
      'response_format' => 'mp3',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $this->api->filterModels(['whisper']),
      '#default_value' => 'whisper-1',
      '#required' => TRUE,
      '#description' => $this->t('The model to use to turn speech into text. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/models/tts']),
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['model'] = $form_state->getValue('model');
    $this->configuration['token_input'] = $form_state->getValue('token_input');
    $this->configuration['token_result'] = $form_state->getValue('token_result');
    $this->configuration['task'] = $form_state->getValue('task');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = trim($this->tokenServices->getTokenData($this->configuration['token_input']));

    $response = $this->api->speechToText(
      $this->configuration['model'],
      $token_value,
      $this->configuration['task']
    );

    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
