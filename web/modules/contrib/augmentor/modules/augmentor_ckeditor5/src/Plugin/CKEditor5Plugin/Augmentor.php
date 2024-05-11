<?php

namespace Drupal\augmentor_ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\augmentor\AugmentorManager;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "augmentor" plugin for CKEditor5.
 */
class Augmentor extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The array list of all available augmentors.
   *
   * @var array
   */
  protected $augmentors;

  /**
   * The augmentor manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * Augmentor constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, AugmentorManager $augmentor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->augmentorManager = $augmentor_manager;
    $this->augmentors = $this->augmentorManager->getAugmentors();
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.augmentor.augmentors'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['augmentors' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $enabled_augmentors = $this->configuration['augmentors'];

    $form['description'] = [
      '#markup' => $this->t('Select the augmentors to be available in the editor.'),
    ];

    foreach ($this->augmentors as $uuid => $augmentor) {
      $form[$uuid] = [
        '#type' => 'checkbox',
        '#title' => $augmentor['label'],
        '#default_value' => array_key_exists($uuid, $enabled_augmentors),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      if ($value && array_key_exists($key, $this->augmentors)) {
        $this->configuration['augmentors'][$key] = $this->augmentors[$key]['label'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $options = $static_plugin_config;
    $config = $this->getConfiguration();
    $dynamic_config = $config ?? $options;
    $this->cleanUpAugmentors($dynamic_config);

    return [
      'augmentors' => [$dynamic_config],
    ];
  }

  /**
   * Removes invalid or deleted augmentors from the config.
   *
   * @param array $config
   *   The config to be cleaned up.
   */
  private function cleanUpAugmentors(&$config) {
    foreach ($config['augmentors'] as $augmentor_id => $augmentor) {
      $augmentor = $this->augmentorManager->getAugmentor($augmentor_id);
      if (!$augmentor) {
        unset($config['augmentors'][$augmentor_id]);
      }
    }
  }

}
