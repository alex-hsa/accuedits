<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the OpenAI openai_eca_execute_chat action.
 *
 * @Action(
 *   id = "openai_eca_execute_chat",
 *   label = @Translation("OpenAI/ChatGPT Chat"),
 *   description = @Translation("Run text through the OpenAI chat endpoint.")
 * )
 */
class Chat extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'model' => 'gpt-4',
      'prompt' => 'Enter your prompt for OpenAI / ChatGPT here.',
      'system' => 'You are an expert in content editing and an assistant to a user writing content for their website. Please return all answers without using first, second, or third person voice.',
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
      '#options' => $this->api->filterModels(['gpt']),
      '#default_value' => $this->configuration['model'],
      '#required' => TRUE,
      '#description' => $this->t('Select which model to use to analyze text. See the <a href="@link">model overview</a> for details about each model. Note that newer GPT models may be invite only.', ['@link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#default_value' => $this->configuration['prompt'],
      '#description' => $this->t('Enter your text here. When submitted, OpenAI will generate a response from its Chats endpoint. Based on the complexity of your text, OpenAI traffic, and other factors, a response can sometimes take up to 10-15 seconds to complete. Please allow the operation to finish. Be cautious not to exceed the requests per minute quota (20/Minute by default), or you may be temporarily blocked.'),
      '#required' => TRUE,
    ];

    $form['system'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Profile'),
      '#default_value' => $this->configuration['system'],
      '#description' => $this->t('The "profile" helps set the behavior of the ChatGPT response. You can change/influence how it response by adjusting the above instruction.'),
      '#required' => TRUE,
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
      '#description' => $this->t('The maximum number of tokens to generate. The token count of your prompt plus max_tokens cannot exceed the model\'s context length. Check the <a href="@link">models overview</a> for more details.', ['@link' => 'https://platform.openai.com/docs/models/gpt-4']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['model'] = $form_state->getValue('model');
    $this->configuration['prompt'] = $form_state->getValue('prompt');
    $this->configuration['system'] = $form_state->getValue('system');
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

    $messages = [
      ['role' => 'system', 'content' => $this->configuration['system']],
      ['role' => 'user', 'content' => trim($prompt)]
    ];

    $response = $this->api->chat(
      $this->configuration['model'],
      $messages,
      floatval($this->configuration['temperature']),
      (int) $this->configuration['max_tokens']
    );

    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
