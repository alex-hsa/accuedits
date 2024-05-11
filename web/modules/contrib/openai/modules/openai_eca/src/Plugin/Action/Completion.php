<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the OpenAI openai_eca_execute_completion action.
 *
 * @Action(
 *   id = "openai_eca_execute_completion",
 *   label = @Translation("OpenAI/ChatGPT Completion"),
 *   description = @Translation("Run text through the OpenAI completion endpoint.")
 * )
 */
class Completion extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'model' => 'gpt-4',
      'prompt' => 'Enter your prompt for OpenAI / ChatGPT here.',
      'temperature' => '0.4',
      'max_tokens' => 256,
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
      '#options' => $this->api->filterModels(['gpt', 'text']),
      '#default_value' => $this->configuration['model'],
      '#required' => TRUE,
      '#description' => $this->t('Select which model to use to analyze text. See the <a href="@link">model overview</a> for details about each model. Note that newer GPT models may be invite only.', ['@link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#default_value' => $this->configuration['prompt'],
      '#required' => TRUE,
      '#description' => $this->t('What do you want OpenAI / ChatGPT to do with the data using the selected model.'),
    ];

    $form['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .1,
      '#default_value' => $this->configuration['temperature'],
      '#required' => TRUE,
      '#description' => $this->t('What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.'),
    ];

    $form['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max tokens'),
      '#min' => 128,
      '#step' => 1,
      '#default_value' => $this->configuration['max_tokens'] ?? 256,
      '#required' => TRUE,
      '#description' => $this->t('The maximum number of tokens to generate in the completion. The token count of your prompt plus max_tokens cannot exceed the model\'s context length. Check the <a href="@link">models overview</a> for more details.', ['@link' => 'https://platform.openai.com/docs/models/gpt-4']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['model'] = $form_state->getValue('model');
    $this->configuration['prompt'] = $form_state->getValue('prompt');
    $this->configuration['temperature'] = $form_state->getValue('temperature');
    $this->configuration['max_tokens'] = $form_state->getValue('max_tokens');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = $this->tokenServices->getTokenData($this->configuration['token_input']);
    $prompt = $this->tokenServices->replace($this->configuration['prompt'], [$this->configuration['token_input'] => $token_value->getValue()]);

    $response = $this->api->completions(
      $this->configuration['model'],
      trim($prompt),
      floatval($this->configuration['temperature']),
      (int) $this->configuration['max_tokens']
    );

    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
