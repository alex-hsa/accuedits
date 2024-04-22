<?php

namespace Drupal\frontend_editing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\paragraphs_edit\Form\ParagraphDeleteForm as ParagraphDeleteFormBase;

/**
 * Overriden ParagraphDeleteForm class from paragraph_edit module with the fix.
 *
 * The fix is to delete the paragraph itself after the parent entity is saved.
 * And also to have correct cancel url, because paragraph entity has no
 * canonical url template.
 *
 * @see https://www.drupal.org/project/paragraphs_edit/issues/3343465
 */
class ParagraphDeleteForm extends ParagraphDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parent = $this->entity->getParentEntity();
    $parent_field = $this->lineageInspector()->getParentField($this->entity);
    $parent_field_item = $this->lineageInspector()->getParentFieldItem($this->entity, $parent_field);

    $parent_field->removeItem($parent_field_item->getName());
    $root_parent = $this->lineageInspector()->getRootParent($this->entity);
    if ($this->lineageRevisioner()->shouldCreateNewRevision($root_parent)) {
      $this->lineageRevisioner()->saveNewRevision($parent);
    }
    else {
      $parent->save();
    }
    // Delete the paragraph itself.
    $this->entity->delete();
    // Redirect to the root parent entity.
    $form_state->setRedirectUrl($this->getCancelUrl());
    // Display and log messages.
    $this->messenger()->addStatus($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $paragraph = $this->getEntity();
    $entity = $this->lineageInspector()->getRootParent($paragraph);
    return $entity->hasLinkTemplate('canonical') ? $entity->toUrl() : Url::fromRoute('<front>');
  }

}
