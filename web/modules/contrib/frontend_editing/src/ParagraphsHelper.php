<?php

namespace Drupal\frontend_editing;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\frontend_editing\Event\FrontendEditingEvents;
use Drupal\frontend_editing\Event\ParagraphAddAccess;
use Drupal\frontend_editing\Event\ParagraphMoveAccess;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\paragraphs_edit\ParagraphLineageInspector;
use Drupal\paragraphs_edit\ParagraphLineageRevisioner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ParagraphsHelper contains methods to manage paragraphs crud operations.
 *
 * @package frontend_editing
 */
class ParagraphsHelper implements ParagraphsHelperInterface {

  /**
   * The lineage inspector.
   *
   * @var \Drupal\paragraphs_edit\ParagraphLineageInspector
   */
  protected $lineageInspector;

  /**
   * The lineage revisioner.
   *
   * @var \Drupal\paragraphs_edit\ParagraphLineageRevisioner
   */
  protected $lineageRevisioner;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ParagraphsHelper constructor.
   *
   * @param \Drupal\paragraphs_edit\ParagraphLineageInspector $paragraph_lineage_inspector
   *   The lineage inspector.
   * @param \Drupal\paragraphs_edit\ParagraphLineageRevisioner $paragraph_lineage_revisioner
   *   The lineage revisioner.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ParagraphLineageInspector $paragraph_lineage_inspector, ParagraphLineageRevisioner $paragraph_lineage_revisioner, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, AccountProxyInterface $current_user, EventDispatcherInterface $event_dispatcher) {
    $this->lineageInspector = $paragraph_lineage_inspector;
    $this->lineageRevisioner = $paragraph_lineage_revisioner;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->currentUser = $current_user;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function allowUp(ParagraphInterface $paragraph) {
    return $this->allow($paragraph, 'up');
  }

  /**
   * {@inheritdoc}
   */
  public function allowDown(ParagraphInterface $paragraph) {
    return $this->allow($paragraph, 'down');
  }

  /**
   * {@inheritdoc}
   */
  public function allowAdd($parent_type, $parent, $parent_field_name) {
    return $this->checkParagraphAddAccess($parent_type, $parent, $parent_field_name);
  }

  /**
   * Checks access for adding a paragraph of certain type to given parent.
   *
   * @param string $parent_type
   *   The paragraph parent type.
   * @param mixed $parent
   *   The id of the parent.
   * @param string $parent_field_name
   *   The field name of the parent.
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type
   *   The paragraph type.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function checkParagraphAddAccess($parent_type, $parent, $parent_field_name, ParagraphsTypeInterface $paragraphs_type = NULL) {
    if (!$this->currentUser->hasPermission('add paragraphs')) {
      return AccessResult::forbidden('User does not have permission to add paragraphs.');
    }
    try {
      $parent_entity = $this->entityTypeManager->getStorage($parent_type)
        ->load($parent);
      $parent_entity = $this->entityRepository->getTranslationFromContext($parent_entity);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return AccessResult::forbidden();
    }
    if (!$parent_entity || !$parent_entity->access('update') || !$parent_entity->hasField($parent_field_name)) {
      return AccessResult::forbidden();
    }
    // Check that the given paragraphs type is allowed.
    $parent_field_definition = $parent_entity->get($parent_field_name)->getFieldDefinition();
    if ($parent_entity->isTranslatable() && !$parent_entity->isDefaultTranslation() && !$parent_field_definition->isTranslatable()) {
      return AccessResult::forbidden('The parent entity paragraph field is not translatable.');
    }
    $cardinality = $parent_field_definition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality > 0 && $parent_entity->get($parent_field_name)->count() == $cardinality) {
      return AccessResult::forbidden('The parent entity paragraph field has reached its maximum cardinality.');
    }
    if ($paragraphs_type) {
      // Check that the given paragraphs type is allowed.
      $handler_settings = $parent_field_definition->getSetting('handler_settings');
      if (!empty($handler_settings['target_bundles'])) {
        if (!empty($handler_settings['negate'])) {
          if (in_array($paragraphs_type->id(), $handler_settings['target_bundles'])) {
            return AccessResult::forbidden();
          }
        }
        elseif (!in_array($paragraphs_type->id(), $handler_settings['target_bundles'])) {
          return AccessResult::forbidden();
        }
      }
    }
    $event = new ParagraphAddAccess($parent_entity, $parent_field_name, $paragraphs_type);
    $this->eventDispatcher->dispatch($event, FrontendEditingEvents::FE_PARAGRAPH_ADD_ACCESS);
    return $event->getAccessResult();
  }

  /**
   * {@inheritdoc}
   */
  public function allowAddType(ParagraphsTypeInterface $paragraphs_type, $parent_type, $parent, $parent_field_name) {
    return $this->checkParagraphAddAccess($parent_type, $parent, $parent_field_name, $paragraphs_type);
  }

  /**
   * Checks if the paragraph can be moved up or down.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to check.
   * @param string $operation
   *   The operation to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function allow(ParagraphInterface $paragraph, $operation) {
    if (!$this->currentUser->hasPermission('move paragraphs')) {
      return AccessResult::forbidden('User does not have permission to move paragraphs.');
    }
    // Check that the operation is valid.
    if (!in_array($operation, ['up', 'down'])) {
      return AccessResult::forbidden();
    }
    if ($paragraph->isNew()) {
      return AccessResult::forbidden();
    }
    // Get paragraph parent entity.
    $parent = $paragraph->getParentEntity();
    // Load the parent entity translation according to context, if it exists.
    // This is needed in case the paragraph is not translated yet, so it loads
    // the source version of the parent entity.
    $parent = $this->entityRepository->getTranslationFromContext($parent);
    // Check that the parent entity exists and the user has update access.
    if (!$parent || !$parent->access('update')) {
      return AccessResult::forbidden();
    }
    // Check that the parent entity has the paragraph field.
    $parent_field_name = $paragraph->get('parent_field_name')->value;
    if (!$parent->hasField($parent_field_name) || $parent->get($parent_field_name)->isEmpty()) {
      return AccessResult::forbidden();
    }
    // By default, paragraph reference fields do not support translations. For
    // this reason we need to check in case the parent entity is translatable
    // that current translation is the default one and that the paragraph field
    // supports translations. In other case do not allow to move the paragraph,
    // because it will break the sync translation and can change the way the
    // paragraphs are sorted for other translations.
    // There are modules that allow async translation of paragraphs. In this
    // case it will still be possible to do, because then the field definition
    // will identify that the field is translatable.
    if ($parent->isTranslatable() && !$parent->isDefaultTranslation() && !$parent->get($parent_field_name)->getFieldDefinition()->isTranslatable()) {
      return AccessResult::forbidden();
    }
    // Get the paragraph items.
    $paragraph_items = $parent->get($parent_field_name)->getValue();
    if ($operation == 'up') {
      $item = reset($paragraph_items);
    }
    else {
      $item = end($paragraph_items);
    }
    if ($item['target_id'] == $paragraph->id()) {
      return AccessResult::forbidden();
    }
    // Allow other modules to react to the move operation only if all other
    // checks passed.
    $event = new ParagraphMoveAccess($paragraph, $operation);
    $this->eventDispatcher->dispatch($event, FrontendEditingEvents::FE_PARAGRAPH_MOVE_ACCESS);
    return $event->getAccessResult();
  }

  /**
   * {@inheritdoc}
   */
  public function move(ParagraphInterface $paragraph, $operation) {
    $allow = $this->allow($paragraph, $operation);
    if (!$allow->isAllowed()) {
      return FALSE;
    }
    $parent = $paragraph->getParentEntity();
    if (!$parent) {
      return FALSE;
    }
    $parent_field_name = $paragraph->get('parent_field_name')->value;
    $paragraph_items = $parent->get($parent_field_name)->getValue();
    if ($operation == 'up') {
      foreach ($paragraph_items as $delta => $paragraph_item) {
        if ($paragraph_item['target_id'] == $paragraph->id()) {
          if ($delta > 0) {
            $prev_paragraph = $paragraph_items[$delta - 1];
            $paragraph_items[$delta - 1] = $paragraph_items[$delta];
            $paragraph_items[$delta] = $prev_paragraph;
          }
          break;
        }
      }
    }
    else {
      $numitems = count($paragraph_items);
      foreach ($paragraph_items as $delta => $paragraph_item) {
        if ($paragraph_item['target_id'] == $paragraph->id()) {
          if ($delta < $numitems) {
            $next_paragraph = $paragraph_items[$delta + 1];
            $paragraph_items[$delta + 1] = $paragraph_items[$delta];
            $paragraph_items[$delta] = $next_paragraph;
          }
          break;
        }
      }
    }
    $parent->get($parent_field_name)->setValue($paragraph_items);
    $root_parent = $this->lineageInspector->getRootParent($paragraph);
    if ($this->lineageRevisioner->shouldCreateNewRevision($root_parent)) {
      $this->lineageRevisioner->saveNewRevision($parent);
    }
    else {
      $parent->save();
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(ParagraphInterface $paragraph) {
    $entity = $this->lineageInspector->getRootParent($paragraph);
    return $entity->hasLinkTemplate('canonical') ? $entity->toUrl() : Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getParagraphRootParent(ParagraphInterface $paragraph) {
    return $this->lineageInspector->getRootParent($paragraph);
  }

}
