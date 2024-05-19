<?php

namespace Drupal\content_templates;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of content template entities.
 *
 * @ingroup content_templates
 */
class ContentTemplateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['category'] = $this->t('Category');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\content_templates\Entity\ContentTemplate $entity */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.node.canonical',
      ['node' => $entity->get('field_source')->target_id]
    );
    $row['category'] = $entity->get('field_category')->entity ? $entity->get('field_category')->entity->label() : $this->t('Uncategorized');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $node = $entity->get('field_source')->entity;
    return parent::getOperations($node);
  }

}
