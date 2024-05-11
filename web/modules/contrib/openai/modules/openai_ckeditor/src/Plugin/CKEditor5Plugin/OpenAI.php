<?php

declare(strict_types=1);

namespace Drupal\openai_ckeditor\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\EditorInterface;
use Drupal\openai\OpenAIApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 OpenAI Completion plugin configuration.
 */
class OpenAI extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface, CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * The default configuration for this plugin.
   *
   * @var string[][]
   */
  const DEFAULT_CONFIGURATION = [
    'completion' => [
      'enabled' => FALSE,
      'model' => 'gpt-3.5-turbo',
      'temperature' => 0.2,
      'max_tokens' => 512,
    ]
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * OpenAI CKEditor plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\openai\OpenAIApi $api
   *    The OpenAI API wrapper.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, OpenAIApi $api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openai.api'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['completion'] = [
      '#title' => $this->t('Text completion'),
      '#type' => 'details',
      '#description' => $this->t('The following setting controls the behavior of the text completion, translate, tone, and summary actions in CKEditor.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['completion']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->configuration['completion']['enabled'] ?? FALSE,
      '#description' => $this->t('Enable this editor feature.'),
    ];

    $models = $this->api->filterModels(['gpt', 'text']);

    $form['completion']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default model'),
      '#options' => $models,
      '#default_value' => $this->configuration['completion']['model'] ?? 'gpt-3.5-turbo',
      '#description' => $this->t('Select which model to use to analyze text. See the <a href=":link">model overview</a> for details about each model. Note that newer GPT models may be invite only.', [':link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['completion']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .1,
      '#default_value' => $this->configuration['completion']['temperature'] ?? '0.2',
      '#description' => $this->t('What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.'),
    ];

    $form['completion']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max tokens'),
      '#min' => 128,
      '#step' => 1,
      '#default_value' => $this->configuration['completion']['max_tokens'] ?? '128',
      '#description' => $this->t('The maximum number of tokens to generate in the completion. The token count of your prompt plus max_tokens cannot exceed the model\'s context length. Check the <a href=":link">models overview</a> for more details.', [':link' => 'https://platform.openai.com/docs/models/gpt-4']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $model = $values['completion']['model'];
    $max_tokens = (int) $values['completion']['max_tokens'];

    switch ($model) {
      case 'gpt-4':
      case 'gpt-4-0314':
        if ($max_tokens > 8192) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 8192 tokens. Please reduce the max token value to 8192 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo':
      case 'gpt-3.5-turbo-0301':
        if ($max_tokens > 4096) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4096 tokens. Please reduce the max token value to 4096 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo-16k':
        if ($max_tokens > 16384) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 16384 tokens. Please reduce the max token value to 16384 or lower.'));
        }
        break;
      case 'text-davinci-003':
        if ($max_tokens > 4097) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4097 tokens. Please reduce the max token value to 4097 or lower.'));
        }
        break;
      case 'text-curie-001':
      case 'text-babage-001':
      case 'text-ada-001':
        if ($max_tokens > 2049) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 2049 tokens. Please reduce the max token value to 2049 or lower.'));
        }
        break;
      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configuration['completion']['enabled'] = (bool) $values['completion']['enabled'];
    $this->configuration['completion']['model'] = $values['completion']['model'];
    $this->configuration['completion']['temperature'] = floatval($values['completion']['temperature']);
    $this->configuration['completion']['max_tokens'] = (int) $values['completion']['max_tokens'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $options = $static_plugin_config;
    $config = $this->getConfiguration();

    return [
      'openai_ckeditor_openai' => [
        'completion' => [
          'enabled' => $config['completion']['enabled'] ?? $options['completion']['enabled'],
          'model' => $config['completion']['model'] ?? $options['completion']['model'],
          'temperature' => $config['completion']['temperature'] ?? $options['completion']['temperature'],
          'max_tokens' => $config['completion']['max_tokens'] ?? $options['completion']['max_tokens'],
        ]
      ]
    ];
  }
}
