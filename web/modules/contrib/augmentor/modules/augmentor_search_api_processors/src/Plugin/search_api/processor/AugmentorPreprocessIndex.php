<?php

namespace Drupal\augmentor_search_api_processors\Plugin\search_api\processor;

use Drupal\augmentor\AugmentorManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a given augmentor during the index preprocess.
 *
 * @SearchApiProcessor(
 *   id = "augmentor_preprocess_index",
 *   label = @Translation("Augmentor Preprocess Index"),
 *   description = @Translation("Executes a given augmentor during the index preprocess."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = -10,
 *   }
 * )
 */
class AugmentorPreprocessIndex extends FieldsProcessorPluginBase {

  /**
   * The augmentor manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * AugmentorProcessor constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, AugmentorManager $augmentor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->augmentorManager = $augmentor_manager;
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
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'augmentor' => [],
      'response_key' => '',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['augmentor'] = [
      '#type' => 'select',
      '#title' => $this->t('Augmentor'),
      '#description' => $this->t('Select the augmentor to use as a processor.'),
      '#options' => $this->getAugmentorOptions(),
      '#default_value' => $this->configuration['augmentor'] ?? '',
      '#required' => TRUE,
    ];

    $form['response_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Response key'),
      '#default_value' => $this->configuration['response_key'] ?? 'default',
      '#description' => $this->t('The key to extract from the augmentor response.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Returns the list of available augmentors.
   *
   * @return array
   *   The list of available augmentors.
   */
  private function getAugmentorOptions(): array {
    $options = [];
    foreach ($this->augmentorManager->getAugmentors() as $uuid => $augmentor) {
      $options[$uuid] = $augmentor['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    $augmentor = $this->augmentorManager->getAugmentor($this->configuration['augmentor']);
    if (!$augmentor) {
      return;
    }
    $result = $augmentor->execute($value);

    if (!isset($result[$this->configuration['response_key']])) {
      return;
    }

    $value = $result[$this->configuration['response_key']];
  }

}
