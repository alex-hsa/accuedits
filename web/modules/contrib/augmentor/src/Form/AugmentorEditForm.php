<?php

namespace Drupal\augmentor\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an edit form for augmentor_entities.
 *
 * @internal
 */
class AugmentorEditForm extends AugmentorFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $augmentor = NULL) {
    $form = parent::buildForm($form, $form_state, $augmentor);
    $form['#title'] = $this->t('Edit %label augmentor', ['%label' => $this->augmentor->label()]);
    $form['#tree'] = TRUE;

    $form['preview_section'] = [
      '#type' => 'fieldset',
      '#title' => t('Preview'),
      '#prefix' => '<div id="preview-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    $preview_output = $form_state->get('preview_output');

    if (isset($preview_output)) {
      $form['preview_section']['test_output'] = [
        '#type' => 'html_tag',
        '#title' => $this->t('Output'),
        '#tag' => 'pre',
        '#prefix' => '<h3>Output</h3>',
        '#value' => '<mark>' . $preview_output . '</mark>',
      ];
    }

    $form['preview_section']['intro'] = [
      '#type' => 'markup',
      '#markup' => t('<h5>Important: You need to save the configuration before you can use the preview functionality.</h5>'),
    ];

    $form['preview_section']['test_input'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Input'),
      '#description' => $this->t('The test input to be augmented.'),
    ];

    $form['preview_section']['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#button_type' => 'primary',
      '#submit' => ['\Drupal\augmentor\Form\AugmentorEditForm::previewAugmentor'],
      '#ajax' => [
        'callback' => [
          $this,
          '\Drupal\augmentor\Form\AugmentorEditForm::previewCallback',
        ],
        'wrapper' => 'preview-fieldset-wrapper',
      ],
    ];

    $form['actions']['submit']['#value'] = $this->t('Update augmentor');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirectUrl(Url::fromRoute('augmentor.augmentor_edit_form', ['augmentor' => $this->augmentor->getUuid()]));
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the messages in it.
   */
  public static function previewCallback(array &$form, FormStateInterface $form_state) {
    return $form['preview_section'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function previewAugmentor(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $test_input = $values['preview_section']['test_input'];

    if (!empty($test_input)) {
      $output = \Drupal::service('plugin.manager.augmentor.augmentors')->executeAugmentor(
        $form_state->getValue('uuid'),
        $test_input
      );

      $form_state->set('preview_output', Json::encode(
        $output,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
      ));

      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAugmentor($augmentor) {
    return $this->augmentorManager->getAugmentor($augmentor);
  }

}
