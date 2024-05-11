<?php

declare(strict_types=1);

namespace Drupal\openai_prompt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai\OpenAIApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to prompt OpenAI for answers.
 */
class PromptForm extends FormBase {

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
    return 'openai_prompt_prompt';
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
    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter your prompt here. When submitted, OpenAI will generate a response. Please note that each query counts against your API usage for OpenAI.'),
      '#description' => $this->t('Based on the complexity of your prompt, OpenAI traffic, and other factors, a response can sometimes take up to 10-15 seconds to complete. Please allow the operation to finish.'),
      '#required' => TRUE,
    ];

    $models = $this->api->filterModels(['text']);

    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Options'),
      '#description' => t('Set various options related to how ChatGPT generates its response.'),
      '#open' => FALSE,
    );

    $form['options']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model to use'),
      '#options' => $models,
      '#default_value' => 'text-davinci-003',
      '#description' => $this->t('Select which model to use to analyze text. See the <a href=":link">model overview</a> for details about each model.', [':link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['options']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .1,
      '#default_value' => '0.4',
      '#description' => $this->t('What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.'),
    ];

    $form['options']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max tokens'),
      '#min' => 128,
      '#max' => 4097,
      '#step' => 1,
      '#default_value' => '128',
      '#description' => $this->t('The maximum number of tokens to generate in the completion. The token count of your prompt plus max_tokens cannot exceed the model\'s context length.'),
    ];

    $form['response'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Response from OpenAI'),
      '#attributes' =>
        [
          'readonly' => 'readonly',
        ],
      '#prefix' => '<div id="openai-prompt-response">',
      '#suffix' => '</div>',
      '#description' => $this->t('The response from OpenAI will appear in the textbox above.')
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ask OpenAI'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-prompt-response',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $model = $form_state->getValue('model');
    $max_tokens = (int) $form_state->getValue('max_tokens');

    switch ($model) {
      case 'text-davinci-003':
        if ($max_tokens > 4097) {
          $form_state->setError($form['max_tokens'], $this->t('The model you have selected only supports a maximum of 4097 tokens. Please reduce the max token value to 4097 or lower.'));
        }
        break;
      case 'text-curie-001':
      case 'text-babage-001':
      case 'text-ada-001':
        if ($max_tokens > 2049) {
          $form_state->setError($form['max_tokens'], $this->t('The model you have selected only supports a maximum of 2049 tokens. Please reduce the max token value to 2049 or lower.'));
        }
        break;
      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(array &$form, FormStateInterface $form_state) {
    $prompt = $form_state->getValue('prompt');
    $model = $form_state->getValue('model');
    $temperature = $form_state->getValue('temperature');
    $max_tokens = $form_state->getValue('max_tokens');

    $response = $this->api->completions($model, $prompt, $temperature, $max_tokens);
    $form['response']['#value'] = trim($response) ?? $this->t('No answer was provided.');
    return $form['response'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
