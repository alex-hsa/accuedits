<?php

namespace Drupal\frontend_editing\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure frontend_editing entity types and bundles.
 */
class EntityTypesBundlesForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->bundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'frontend_editing_settings_entity_types_bundles';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['frontend_editing.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('frontend_editing.settings');

    $entity_types = $this->entityTypeManager->getDefinitions();
    $labels = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      $labels[$entity_type_id] = $entity_type->getLabel() ?: $entity_type_id;
    }
    asort($labels);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'frontend_editing/settings.admin';

    $form['frontend_editing'] = [
      '#type' => 'container',
      '#name' => 'frontend_editing',
    ];

    $form['frontend_editing']['entity_types'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Enabled entity types and bundles'),
    ];

    foreach ($labels as $entity_type_id => $label) {
      $form['frontend_editing']['entity_types'][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $label,
        '#group' => 'frontend_editing][entity_types',
        '#attributes' => [
          'class' => ['entity-type-tab'],
        ],
      ];
      $bundle_info = $this->bundleInfo->getBundleInfo($entity_type_id);
      $options = [];
      foreach ($bundle_info as $bundle_name => $bundle) {
        $options[$bundle_name] = $bundle['label'];
      }
      $form['frontend_editing']['entity_types'][$entity_type_id]['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $options,
        '#attributes' => [
          'class' => ['entity-type-bundles'],
        ],
        '#default_value' => $config->get("entity_types.$entity_type_id") ?? [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_types_bundles = $form_state->getValue([
      'frontend_editing',
      'entity_types',
    ]);
    foreach ($entity_types_bundles as $entity_type => $bundles) {
      if (!is_array($bundles)) {
        unset($entity_types_bundles[$entity_type]);
        continue;
      }
      $entity_types_bundles[$entity_type] = array_filter($bundles['bundles']);
      $entity_types_bundles[$entity_type] = array_values($entity_types_bundles[$entity_type]);
    }
    $entity_types_bundles = array_filter($entity_types_bundles);
    $this->config('frontend_editing.settings')
      ->set('entity_types', $entity_types_bundles)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
