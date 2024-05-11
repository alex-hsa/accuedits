<?php

namespace Drupal\augmentor\Form;

use Drupal\augmentor\AugmentorManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting an augmentor.
 *
 * @internal
 */
class AugmentorDeleteForm extends ConfirmFormBase {

  /**
   * The augmentor manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * Constructs a new AugmentorDeleteForm.
   *
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager.
   */
  public function __construct(AugmentorManager $augmentor_manager) {
    $this->augmentorManager = $augmentor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.augmentor.augmentors'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this augmentor?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('augmentor.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'augmentor_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $augmentor = NULL) {
    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $augmentor,
    ];
    return parent::buildForm($form, $form_state, $augmentor);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->augmentorManager->getAugmentorConfig();
    $augmentors = $config->get('augmentors');
    unset($augmentors[$form_state->getValue('uuid')]);
    $config->set('augmentors', $augmentors);
    $config->save();

    $this->messenger()->addStatus($this->t('The augmentor has been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
