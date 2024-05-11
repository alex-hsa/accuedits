<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the OpenAI openai_eca_execute_tts action.
 *
 * @Action(
 *   id = "openai_eca_execute_tts",
 *   label = @Translation("OpenAI/ChatGPT Text to Speech"),
 *   description = @Translation("Run text through the OpenAI text to speech endpoint.")
 * )
 */
class TextToSpeech extends OpenAIActionBase {

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
      '#options' => $this->api->filterModels(['tts']),
      '#default_value' => 'tts-1',
      '#required' => TRUE,
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['model'] = $form_state->getValue('model');
    $this->configuration['token_input'] = $form_state->getValue('token_input');
    $this->configuration['token_result'] = $form_state->getValue('token_result');
    $this->configuration['voice'] = $form_state->getValue('voice');
    $this->configuration['response_format'] = $form_state->getValue('response_format');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = trim($this->tokenServices->getTokenData($this->configuration['token_input']));

    if (mb_strlen($token_value) > 4096) {
      throw new \RuntimeException('The input cannot exceed 4096 characters.');
    }

    $response = $this->api->textToSpeech(
      $this->configuration['model'],
      $token_value,
      $this->configuration['voice'],
      $this->configuration['response_format']
    );

    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
