<?php

declare(strict_types=1);

namespace Drupal\openai_chatgpt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form interact with the ChatGPT endpoint.
 */
class ChatGptForm extends FormBase {

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
    return 'openai_chatgpt_form';
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
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ask ChatGPT'),
      '#rows' => 1,
      '#description' => $this->t('Enter your text here. When submitted, OpenAI will generate a response from its Chats endpoint. Please note that each query counts against your API usage for OpenAI. Based on the complexity of your text, OpenAI traffic, and other factors, a response can sometimes take up to 10-15 seconds to complete. Please allow the operation to finish. Be cautious not to exceed the requests per minute quota (20/Minute by default), or you may be temporarily blocked.'),
      '#required' => TRUE,
    ];

    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Options'),
      '#description' => t('Set various options related to how ChatGPT generates its response.'),
      '#open' => FALSE,
    );

    $models = $this->api->filterModels(['gpt']);

    $form['options']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $models,
      '#default_value' => 'gpt-3.5-turbo',
      '#description' => $this->t('Select which model to use to analyze text. See the <a href=":link">model overview</a> for details about each model.', [':link' => 'https://platform.openai.com/docs/models/gpt-3.5']),
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
      '#step' => 1,
      '#default_value' => '128',
      '#description' => $this->t('The maximum number of tokens to generate in the completion. The token count of your prompt plus max_tokens cannot exceed the model\'s context length.'),
    ];

    $form['options']['system'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Profile'),
      '#default_value' => 'You are a friendly helpful assistant inside of a Drupal website. Be encouraging and polite and ask follow up questions of the user after giving the answer.',
      '#description' => $this->t('The "profile" helps set the behavior of the ChatGPT response. You can change/influence how it response by adjusting the above instruction. If you want to change this value after starting a conversation, you will need to reload the form first.'),
      '#required' => TRUE,
    ];

    $form['response'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Response from OpenAI'),
      '#attributes' =>
        [
          'readonly' => 'readonly',
        ],
      '#prefix' => '<div id="openai-chatgpt-response">',
      '#suffix' => '</div>',
      '#description' => $this->t('The response from OpenAI will appear in the textbox above.')
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-chatgpt-response',
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
    $model = $form_state->getValue('model');
    $max_tokens = (int) $form_state->getValue('max_tokens');

    switch ($model) {
      case 'gpt-4':
      case 'gpt-4-0314':
        if ($max_tokens > 8192) {
          $form_state->setError($form['options']['max_tokens'], $this->t('The model you have selected only supports a maximum of 8192 tokens. Please reduce the max token value to 8192 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo':
      case 'gpt-3.5-turbo-0301':
        if ($max_tokens > 4096) {
          $form_state->setError($form['options']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4096 tokens. Please reduce the max token value to 4096 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo-16k':
        if ($max_tokens > 16384) {
          $form_state->setError($form['options']['max_tokens'], $this->t('The model you have selected only supports a maximum of 16384 tokens. Please reduce the max token value to 16384 or lower.'));
        }
        break;
      default:
        break;
    }
  }

  /**
   * Render the last response out to the user.
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
    $errors = $form_state->getErrors();
    if (empty($errors)) {
      $storage = $form_state->getStorage();
      $last_response = end($storage['messages']);
      $form['response']['#value'] = trim($last_response['content']) ?? $this->t('No answer was provided.');
    }
    return $form['response'];
  }

  /**
   * Submit the input to OpenAI.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @todo implement exponential backoff retries when quota rate limits are exceeded
   * @see https://platform.openai.com/docs/guides/rate-limits/retrying-with-exponential-backoff
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $text = $form_state->getValue('text');
    $system = $form_state->getValue('system');
    $model = $form_state->getValue('model');
    $temperature = $form_state->getValue('temperature');
    $max_tokens = $form_state->getValue('max_tokens');

    $storage = $form_state->getStorage();

    if (!empty($storage['messages'])) {
      $messages = $storage['messages'];
      $messages[] = ['role' => 'user', 'content' => trim($text)];
    } else {
      $messages = [
        ['role' => 'system', 'content' => trim($system)],
        ['role' => 'user', 'content' => trim($text)]
      ];
    }

    $result = $this->api->chat($model, $messages, $temperature, $max_tokens);
    $messages[] = ['role' => 'assistant', 'content' => $result];
    $form_state->setStorage(['messages' => $messages]);
    $form_state->setRebuild(TRUE);
  }

}
