<?php

namespace Drupal\augmentor_chatgpt\Plugin\Augmentor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Component\Serialization\Json;
use Drupal\augmentor_chatgpt\ChatGptBase;

/**
 * ChatGPT augmentor plugin implementation.
 *
 * @Augmentor(
 *   id = "chatgpt",
 *   label = @Translation("ChatGPT"),
 *   description = @Translation("Given a chat conversation, the model will return a chat completion response."),
 * )
 */
class ChatGpt extends ChatGptBase {

  use DependencySerializationTrait;

  /**
   * Default engine to use when we don't have access to the API.
   */
  const DEFAULT_ENGINE = 'gpt-3.5-turbo';

  /**
   * Default roles to use in the message.
   */
  const MESSAGE_ROLES = [
    'system' => 'System',
    'assistant' => 'Assistant',
    'user' => 'User',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'model' => NULL,
      'messages' => NULL,
      'temperature' => NULL,
      'max_tokens' => NULL,
      'top_p' => NULL,
      'n' => NULL,
      'frequency_penalty' => NULL,
      'presence_penalty' => NULL,
      'user_tracking' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t("Currently, only gpt-3.5-turbo and gpt-3.5-turbo-0301 are <a href='https://platform.openai.com/docs/api-reference/chat/create#chat/create-model' target='_blank'>supported</a>."),
      '#options' => [
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'gpt-3.5-turbo-0301' => 'gpt-3.5-turbo-0301',
      ],
      '#default_value' => $this->configuration['model'] ?? self::DEFAULT_ENGINE,
    ];

    $messages = $this->configuration['messages'] ?? [];
    $num_messages = $form_state->get('num_messages');

    if ($num_messages === NULL) {
      $num_messages = count($messages) - 1;

      if ($num_messages < 1) {
        $num_messages = 1;
      }
    }

    $form_state->set('num_messages', $num_messages);
    $form['#tree'] = TRUE;

    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Messages'),
      '#prefix' => '<div id="messages-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['messages']['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The messages to generate chat completions for, in 
      the format described in the official <a href="https://platform.openai.com/docs/guides/chat/introduction" target="_blank">documentation</a>, for example:<pre>
      role: "system", content: "You are a helpful assistant."
      role: "user", content: "Who won the world series in 2020?"
      role: "assistant", content: "The Los Angeles Dodgers won the World Series in 2020."
      role: "user", content: "{input}"</pre>'),
    ];

    for ($i = 0; $i < $num_messages; $i++) {
      $form['messages'][$i]['role'] = [
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#options' => self::MESSAGE_ROLES,
        '#default_value' => $messages[$i]['role'] ?? 'user',
      ];

      $form['messages'][$i]['content'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Content'),
        '#default_value' => $messages[$i]['content'] ?? '{input}',
        '#description' => $this->t('The content of the message. 
          You can use {input} to insert the input text for this augmentor.'),
      ];
    }

    $form['messages']['actions'] = [
      '#type' => 'actions',
    ];

    $form['messages']['actions']['add_message'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more message'),
      '#submit' => ['\Drupal\augmentor_chatgpt\Plugin\Augmentor\ChatGpt::addOne'],
      '#ajax' => [
        'callback' => [
          $this,
          '\Drupal\augmentor_chatgpt\Plugin\Augmentor\ChatGpt::addmoreCallback',
        ],
        'wrapper' => 'messages-fieldset-wrapper',
      ],
    ];

    if ($num_messages > 1) {
      $form['messages']['actions']['remove_message'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove last message'),
        '#submit' => ['\Drupal\augmentor_chatgpt\Plugin\Augmentor\ChatGpt::removeCallback'],
        '#ajax' => [
          'callback' => [
            $this,
            '\Drupal\augmentor_chatgpt\Plugin\Augmentor\ChatGpt::addmoreCallback',
          ],
          'wrapper' => 'messages-fieldset-wrapper',
        ],
      ];
    }

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#description' => t('See https://beta.openai.com/docs/api-reference/completions for more information.'),
    ];

    $form['advanced']['temperature'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Temperature'),
      '#default_value' => $this->configuration['temperature'] ?? 1,
      '#description' => $this->t('What sampling temperature to use. Higher values means the model will take more risks. Try 0.9 for more creative applications, and 0 (argmax sampling) for ones with a well-defined answer.
        We generally recommend altering this or top_p but not both.'),
    ];

    $form['advanced']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#default_value' => $this->configuration['max_tokens'] ?? 100,
      '#description' => $this->t("The maximum number of tokens to generate in the completion.
        The token count of your prompt plus max_tokens cannot exceed the model's context length. Most models have a context length of 2048 tokens (except for the newest models, which support 4096)."),
    ];

    $form['advanced']['top_p'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Top P'),
      '#default_value' => $this->configuration['top_p'] ?? 0,
      '#description' => $this->t('An alternative to sampling with temperature, called nucleus sampling, where the model considers the results of the tokens with top_p probability mass. So 0.1 means only the tokens comprising the top 10% probability mass are considered.
        We generally recommend altering this or temperature but not both.'),
    ];

    $form['advanced']['n'] = [
      '#type' => 'number',
      '#title' => $this->t('N'),
      '#default_value' => $this->configuration['n'] ?? 1,
      '#description' => $this->t('How many completions to generate for each prompt. 
        Note: Because this parameter generates many completions, it can quickly consume your token quota. Use carefully and ensure that you have reasonable settings for max_tokens and stop.'),
    ];

    $form['advanced']['frequency_penalty'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Frequency Penalty'),
      '#default_value' => $this->configuration['frequency_penalty'] ?? 0,
      '#description' => $this->t("Number between -2.0 and 2.0. Positive values penalize new tokens based on their existing frequency in the text so far, decreasing the model's likelihood to repeat the same line verbatim."),
    ];

    $form['advanced']['presence_penalty'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#title' => $this->t('Presence Penalty'),
      '#default_value' => $this->configuration['presence_penalty'] ?? 0,
      '#description' => $this->t("Number between -2.0 and 2.0. Positive values penalize new tokens based on whether they appear in the text so far, increasing the model's likelihood to talk about new topics."),
    ];

    $form['advanced']['user_tracking'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['user_tracking'] ?? TRUE,
      '#description' => $this->t('Enable a unique identifier representing your end-user, which will help OpenAI to monitor and detect abuse.'),
      '#title' => t('User Tracking'),
    ];

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the messages in it.
   */
  public static function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['messages'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addOne(array &$form, FormStateInterface $form_state) {
    $message_field = $form_state->get('num_messages');
    $add_button = $message_field + 1;
    $form_state->set('num_messages', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_messages');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_messages', $remove_button);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['model'] = $form_state->getValue('model');
    $this->configuration['messages'] = $form_state->getValue('messages');
    $advance_settings = $form_state->getValue('advanced');
    $this->configuration['temperature'] = $advance_settings['temperature'];
    $this->configuration['max_tokens'] = $advance_settings['max_tokens'];
    $this->configuration['top_p'] = $advance_settings['top_p'];
    $this->configuration['n'] = $advance_settings['n'];
    $this->configuration['frequency_penalty'] = $advance_settings['frequency_penalty'];
    $this->configuration['presence_penalty'] = $advance_settings['presence_penalty'];
    $this->configuration['user_tracking'] = $advance_settings['user_tracking'];
  }

  /**
   * Creates a completion for the provided prompt and parameters.
   *
   * @param string $input
   *   The text to use as source for the completion manipulation.
   *
   * @return array
   *   The completion output.
   */
  public function execute($input) {
    $message_options = [];

    foreach ($this->configuration['messages'] as $message) {
      if (!empty($message)) {
        $role = $message['role'];
        $content = $message['content'];

        if ($role == 'user') {
          $content = str_replace('{input}', $input, $content);
        }

        $message_options[] = [
          'role' => $role,
          'content' => $content,
        ];
      }
    }

    $options = [
      'model' => $this->configuration['model'],
      'messages' => $message_options,
      'temperature' => (double) $this->configuration['temperature'],
      'max_tokens' => (int) $this->configuration['max_tokens'],
      'top_p' => (double) $this->configuration['top_p'],
      'n' => (int) $this->configuration['n'],
      'frequency_penalty' => (double) $this->configuration['frequency_penalty'],
      'presence_penalty' => (double) $this->configuration['presence_penalty'],
    ];

    if ($this->configuration['user_tracking']) {
      $options['user'] = $this->currentUser->id();
    }

    try {
      $result = Json::decode($this->getClient()->chat($options), TRUE);
      $output = [];

      if (array_key_exists('_errors', $result)) {
        $this->logger->error('OpenAI API error: %message.', [
          '%message' => $result['_errors']['message'],
        ]);

        return [
          '_errors' => $this->t('Error during the chat completion execution, please check the logs for more information.')->render(),
        ];
      }
      else {
        foreach ($result['choices'] as $choice) {
          if ($choice['message']) {
            $output[] = $this->normalizeText($choice['message']['content']);
          }
        }

        $output['default'] = $output;
      }
    }
    catch (\Throwable $error) {
      $this->logger->error('OpenAI API error: %message.', [
        '%message' => $error->getMessage(),
      ]);
      return [
        '_errors' => $this->t('Error during the chat completion execution, please check the logs for more information.')->render(),
      ];
    }

    return $output;
  }

}
