<?php

namespace Drupal\augmentor\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an add form for augmentors.
 *
 * @internal
 */
class AugmentorAddForm extends AugmentorFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $augmentor = NULL) {
    $form = parent::buildForm($form, $form_state, $augmentor);
    $form['#title'] = $this->t('Add Augmentor');
    $form['actions']['submit']['#value'] = $this->t('Add augmentor');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAugmentor($augmentor_id) {
    $this->augmentor = $this->augmentorManager->createInstance($augmentor_id);
    return $this->augmentor;
  }

}
