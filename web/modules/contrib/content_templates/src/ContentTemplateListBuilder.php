<?php

namespace Drupal\content_templates;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
    $header['status'] = $this->t('Status');
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
    $row['status'] = $entity->isPublished() ? $this->t('Active') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $node = $entity->get('field_source')->entity;
    $operations = parent::getOperations($node);
    $operations['view'] = [
      'title' => $this->t('View'),
      'weight' => -10,
      'url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
    ];
    $operations['descendants'] = [
      'title' => $this->t('Descendants'),
      'weight' => 101,
      'url' => Url::fromRoute('content_templates.node.overview', ['node' => $node->id()]),
    ];
    return $operations;
  }

}
