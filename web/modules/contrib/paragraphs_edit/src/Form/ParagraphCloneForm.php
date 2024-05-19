<?php

namespace Drupal\paragraphs_edit\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\paragraphs_edit\ParagraphFormHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ParagraphCloneForm class.
 */
class ParagraphCloneForm extends ContentEntityForm {
  use ParagraphFormHelperTrait;

  /**
   * The entity being cloned by this form.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $originalEntity;

  /**
   * The entityFieldManager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entityTypeRepository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Constructs a ParagraphCloneForm object.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, EntityFieldManagerInterface $entityFieldManager, EntityTypeRepositoryInterface $entityTypeRepository) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeRepository = $entityTypeRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    $account = $this->currentUser();

    // Keep track of the original entity.
    $this->originalEntity = $this->entity;

    // Create a duplicate.
    $paragraph = $this->entity = $this->entity->createDuplicate();
    $paragraph->set('created', $this->time->getRequestTime());
    $paragraph->setOwnerId($account->id());
    $paragraph->setRevisionAuthorId($account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('paragraphs-edit-clone');

    $form['paragraphs_edit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Clone to'),
      '#id' => $wrapper_id,
      '#tree' => TRUE,
    ];

    $potential_destinations = $this->getPotentialCloneDestinations($this->entity->bundle());
    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels();
    $entity_types = array_intersect_key($entity_type_labels, $potential_destinations);

    $form['paragraphs_edit']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::paragraphEditChangeAjax',
        'wrapper' => $wrapper_id,
      ],
    ];

    $bundles = [];
    $entity_type = $form_state->getValue(['paragraphs_edit', 'entity_type'], NULL);
    if (!empty($entity_type)) {
      $bundles = $potential_destinations[$entity_type]['bundles'];
    }

    $form['paragraphs_edit']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundles,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::paragraphEditChangeAjax',
        'wrapper' => $wrapper_id,
      ],
      '#disabled' => empty($entity_type),
    ];

    $selection_settings = [];
    $bundle = $form_state->getValue(['paragraphs_edit', 'bundle'], NULL);
    if (!empty($bundle)) {
      $selection_settings['target_bundles'] = [$bundle => $bundle];
    }

    if (!$entity_type) {
      $entity_type = array_keys($potential_destinations)[0];
    }

    $form['paragraphs_edit']['parent'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Parent'),
      '#target_type' => $entity_type,
      '#selection_handler' => 'default',
      '#selection_settings' => $selection_settings,
      '#required' => TRUE,
      '#disabled' => empty($bundle),
    ];

    $form['paragraphs_edit']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => !empty($bundle) ? $potential_destinations[$entity_type]['fields'][$bundle] : [],
      '#required' => TRUE,
      '#disabled' => empty($bundle),
    ];

    if (count($form['paragraphs_edit']['field']['#options']) == 1) {
      $form['paragraphs_edit']['field']['#default_value'] = key($form['paragraphs_edit']['field']['#options']);
    }

    $form = parent::form($form, $form_state);

    $form['#title'] = $this->t('Clone @lineage', [
      '@lineage' => $this->lineageInspector()->getLineageString($this->originalEntity),
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function paragraphEditChangeAjax($form) {
    return $form['paragraphs_edit'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPotentialCloneDestinations($paragraph_type) {
    $types_with_paragraphs = $this->entityFieldManager->getFieldMapByFieldType('entity_reference_revisions');
    $field_definitions_bundle = [];
    $destinations = [];
    foreach ($types_with_paragraphs as $entity_type_id => $entity_type) {
      $bundles_labels = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($entity_type as $field => $info) {
        foreach ($info['bundles'] as $bundle) {
          if (!isset($field_definitions_bundle[$entity_type_id][$bundle])) {
            $field_definitions_bundle[$entity_type_id][$bundle] = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          }
          /** @var \Drupal\field\FieldConfigInterface $field_definition */
          $field_definition = $field_definitions_bundle[$entity_type_id][$bundle][$field];

          // Check if field accepts paragraphs of this bundle.
          $handler_settings = $field_definition->getSetting('handler_settings');
          $target_bundles = $handler_settings['target_bundles'] ?? '';;
          $target_bundles_options = array_keys($handler_settings['target_bundles_drag_drop'] ?? []);

          if (empty($target_bundles)) {
            $target_bundles = $target_bundles_options;
          } elseif (!empty($handler_settings['negate'])) {
            $target_bundles = array_diff($target_bundles_options, $target_bundles);
          }

          if (
            !empty($target_bundles) &&
            in_array($paragraph_type, $target_bundles)
          ) {
            $destinations[$entity_type_id]['bundles'][$bundle] = $bundles_labels[$bundle]['label'];
            $destinations[$entity_type_id]['fields'][$bundle][$field] = $field_definition->getLabel();
          }
        }
      }
    }

    return $destinations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $destination_entity_type = $form_state->getValue(['paragraphs_edit', 'entity_type']);
    $destination_entity_id = $form_state->getValue(['paragraphs_edit', 'parent']);
    $destination_field = $form_state->getValue(['paragraphs_edit', 'field']);
    if ($destination_entity_id && $destination_field) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $destination_entity */
      $destination_entity = $this->entityTypeManager->getStorage($destination_entity_type)->load($destination_entity_id);
      if ($destination_entity) {
        if (!$destination_entity->access('update')) {
          $form_state->setError($form['paragraphs_edit']['parent'], 'You are not allowed to update this content.');
        }
        if (!$destination_entity->get($destination_field)->access('edit')) {
          $form_state->setError($form['paragraphs_edit']['field'], 'You are not allowed to edit this field.');
        }
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $destination_entity_type = $form_state->getValue(['paragraphs_edit', 'entity_type']);
    $destination_entity_id = $form_state->getValue(['paragraphs_edit', 'parent']);
    $destination_field = $form_state->getValue(['paragraphs_edit', 'field']);
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $destination_entity */
    $destination_entity = $this->entityTypeManager->getStorage($destination_entity_type)->load($destination_entity_id);
    $destination_entity->get($destination_field)->appendItem($this->entity);

    if ($this->lineageRevisioner()->shouldCreateNewRevision($destination_entity)) {
      $this->lineageRevisioner()->saveNewRevision($destination_entity);
    }
    else {
      $destination_entity->save();
    }

    $this->entity = $this->entityTypeManager
      ->getStorage($this->entity->getEntityTypeId())
      ->loadUnchanged($this->entity->id());

    $this->messenger()->addMessage($this->t('Cloned @source_lineage to %destination_lineage', [
      '@source_lineage' => $this->lineageInspector()->getLineageString($this->originalEntity),
      '%destination_lineage' => $this->lineageInspector()->getLineageString($this->entity),
    ]));

    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $request->query->remove('destination');
    }
    $form_state->setRedirectUrl($destination_entity->toUrl());
  }

}
