<?php

namespace Drupal\preview\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Preview configuration form.
 */
class PreviewSettingsForm extends ConfigFormBase {

  public const SETTINGS = 'preview.settings';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $bundleInfo;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a PreviewSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'preview_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#tree' => TRUE,
    ];

    $config = $this->config(self::SETTINGS)->getRawData();

    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $entity_type_id = $definition->id();
        // We already have that for nodes.
        if ($entity_type_id === 'node') {
          continue;
        }

        $form['settings'][$entity_type_id] = [
          '#type' => 'container',
        ];
        $form['settings'][$entity_type_id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $definition->getLabel(),
          '#default_value' => !empty($config[$entity_type_id]),
        ];
        $form['settings'][$entity_type_id]['bundles'] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => $this->t('@label settings', [
            '@label' => $definition->getLabel(),
          ]),
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $entity_type_id . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $bundle_info = $this->bundleInfo->getBundleInfo($definition->id());
        foreach ($bundle_info as $bundle_id => $bundle_data) {
          $form['settings'][$entity_type_id]['bundles'][$bundle_id] = [
            '#type' => 'container',
          ];
          $form['settings'][$entity_type_id]['bundles'][$bundle_id]['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $bundle_data['label'],
            '#default_value' => !empty($config[$entity_type_id][$bundle_id]),
          ];
          $form['settings'][$entity_type_id]['bundles'][$bundle_id]['default_view_mode'] = [
            '#type' => 'radios',
            '#title' => $this->t('Default view mode'),
            '#options' => ['default' => $this->t('Default')] + $this->entityDisplayRepository->getViewModeOptionsByBundle($definition->id(), $bundle_id),
            '#default_value' => !empty($config[$entity_type_id][$bundle_id]) ? $config[$entity_type_id][$bundle_id] : NULL,
            '#states' => [
              'visible' => [
                ':input[name="settings[' . $entity_type_id . '][bundles][' . $bundle_id . '][enabled]"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->getValue('settings');
    $config_data = [];
    foreach ($form_data as $entity_type_id => $type_data) {
      if (!$type_data['enabled']) {
        continue;
      }

      foreach ($type_data['bundles'] as $bundle_id => $bundle_data) {
        if (!$bundle_data['enabled'] || empty($bundle_data['default_view_mode'])) {
          continue;
        }
        $config_data[$entity_type_id][$bundle_id] = $bundle_data['default_view_mode'];
      }
    }

    $config = $this->config(self::SETTINGS);
    $config->setData($config_data);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
