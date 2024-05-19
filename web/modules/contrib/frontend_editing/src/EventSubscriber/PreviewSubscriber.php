<?php

namespace Drupal\frontend_editing\EventSubscriber;

use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs_edit\ParagraphLineageInspector;
use Drupal\preview\Event\PreviewBackLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PreviewSubscriber for `preview` module events.
 *
 * @package Drupal\frontend_editing\EventSubscriber
 */
class PreviewSubscriber implements EventSubscriberInterface {

  /**
   * The lineage inspector.
   *
   * @var \Drupal\paragraphs_edit\ParagraphLineageInspector
   */
  protected $lineageInspector;

  /**
   * PreviewSubscriber constructor.
   *
   * @param \Drupal\paragraphs_edit\ParagraphLineageInspector $paragraph_lineage_inspector
   *   The lineage inspector.
   */
  public function __construct(ParagraphLineageInspector $paragraph_lineage_inspector) {
    $this->lineageInspector = $paragraph_lineage_inspector;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'preview.back_link' => 'onPreviewBackLink',
    ];
  }

  /**
   * Callback for the `preview.back_link` event.
   *
   * @param \Drupal\preview\Event\PreviewBackLink $event
   *   The event object.
   */
  public function onPreviewBackLink(PreviewBackLink $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ParagraphInterface) {
      if ($entity->isNew()) {
        $back_url = Url::fromRoute('frontend_editing.paragraph_add', [
          'parent_type' => $entity->getParentEntity()->getEntityTypeId(),
          'parent' => $entity->getParentEntity()->id(),
          'parent_field_name' => $entity->get('parent_field_name')->value,
          'paragraphs_type' => $entity->bundle(),
        ]);
      }
      else {
        $root_parent = $this->lineageInspector->getRootParent($entity);
        $back_url = Url::fromRoute('paragraphs_edit.edit_form', [
          'root_parent_type' => $root_parent->getEntityTypeId(),
          'root_parent' => $root_parent->id(),
          'paragraph' => $entity->id(),
        ]);
      }
      if (!empty($back_url)) {
        $event->setBackLink($back_url);
      }
    }
  }

}
