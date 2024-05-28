<?php

namespace Drupal\content_templates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Form\NodeDeleteForm as CoreNodeDeleteForm;

/**
 * Class NodeDeleteForm overrides core class.
 *
 * This is needed to add a warning about templates that will be deleted with the
 * node.
 *
 * @package Drupal\content_templates\Form
 */
class NodeDeleteForm extends CoreNodeDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    $template_storage = $this->entityTypeManager->getStorage('content_template');
    $query = $template_storage->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('field_source.target_id', $entity->id());
    $ids = $query->execute();
    if (!empty($ids)) {
      $items = [];
      $content_templates = $template_storage->loadMultiple($ids);
      foreach ($content_templates as $content_template) {
        $items[] = Link::fromTextAndUrl($content_template->label(), Url::fromRoute('content_templates.node.template', ['node' => $entity->id()]));
      }
      $old_description = $form['description'];
      $old_description['#markup'] .= ' ' . $this->t('Do you want to proceed?');
      $form['description'] = [
        'templates_warning' => [
          '#markup' => '<h3>' . $this->t('This page is used as a template. When you delete it the following template(s) will be also deleted:') . '</h3>',
        ],
        'templates_list' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
        'old_description' => $old_description,
      ];
    }
    return $form;
  }

}
