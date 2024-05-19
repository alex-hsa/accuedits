<?php

namespace Drupal\content_templates\Form;

use Drupal\Core\Url;
use Drupal\node\Form\NodeDeleteForm as CoreNodeDeleteForm;

/**
 * Class NodeDeleteForm.
 *
 * @package Drupal\content_templates\Form
 */
class NodeDeleteForm extends CoreNodeDeleteForm {

  /**
   * Notify about possible deletion of the node template.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Question string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getQuestion() {
    $parent_question = parent::getQuestion();
    $entity = $this->getEntity();
    $template_storage = $this->entityTypeManager->getStorage('content_template');
    $query = $template_storage->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('field_source.target_id', $entity->id());
    $ids = $query->execute();
    if (!empty($ids)) {
      $content_templates = $template_storage->loadMultiple($ids);
      foreach ($content_templates as $content_template) {
        $parent_question .= ' ' . $this->t('Content template <a href="@url" target="_blank">@label</a> will be deleted too.', [
          '@label' => $content_template->label(),
          '@url' => Url::fromRoute('content_templates.node.template', ['node' => $entity->id()])->toString(),
        ]);
      }
    }
    return $parent_question;
  }

}
