<?php

namespace Drupal\frontend_editing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs_edit\ParagraphFormHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Paragraph edit forms.
 *
 * @ingroup paragraphs_edit
 */
class ParagraphAddForm extends ContentEntityForm {

  use ParagraphFormHelperTrait;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Make form submit via ajax.
    $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    $form['actions']['submit']['#submit'][] = 'frontend_editing_success';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'frontend_editing/jquery.form';
    $form['#attached']['library'][] = 'frontend_editing/forms_helper';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $entity = parent::getEntityFromRouteMatch($route_match, $entity_type_id);
    $parent_entity_type = $route_match->getParameter('parent_type');
    $parent_id = $route_match->getParameter('parent');
    $parent_field = $route_match->getParameter('parent_field_name');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $this->entityTypeManager->getStorage($parent_entity_type)->load($parent_id);
    $entity->setParentEntity($parent, $parent_field);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    if ($this->entity->id()) {
      $parent_entity = $this->entity->getParentEntity();
      $parent_field = $this->entity->get('parent_field_name')->value;
      $current_paragraph = $this->routeMatch->getParameter('current_paragraph');
      $before = $this->routeMatch->getParameter('before') ?? FALSE;
      if ($current_paragraph) {
        $values = $parent_entity->get($parent_field)->getValue();
        $new_values = [];
        foreach ($values as $value) {
          if ($before && $value['target_id'] == $current_paragraph) {
            $new_values[] = [
              'target_id' => $this->entity->id(),
              'target_revision_id' => $this->entity->getRevisionId(),
            ];
          }
          $new_values[] = $value;
          if (!$before && $value['target_id'] == $current_paragraph) {
            $new_values[] = [
              'target_id' => $this->entity->id(),
              'target_revision_id' => $this->entity->getRevisionId(),
            ];
          }
        }
        $parent_entity->set($parent_field, $new_values);
      }
      else {
        $parent_entity->get($parent_field)->appendItem($this->entity);
      }
      $root_entity = $parent_entity;
      if ($parent_entity instanceof ParagraphInterface) {
        $root_entity = $this->lineageInspector()->getRootParent($parent_entity);
      }
      if ($this->lineageRevisioner()->shouldCreateNewRevision($root_entity)) {
        $this->lineageRevisioner()->saveNewRevision($parent_entity);
      }
      else {
        $parent_entity->save();
      }
    }
    return $result;
  }

}
