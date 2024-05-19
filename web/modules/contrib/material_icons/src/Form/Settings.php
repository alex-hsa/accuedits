<?php

namespace Drupal\material_icons\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\material_icons\Traits\MaterialIconsSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin settings for Material Icons.
 *
 * @package Drupal\material_icons\Form
 */
class Settings extends ConfigFormBase {

  use MaterialIconsSettings;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['material_icons.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'material_icons_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('material_icons.settings');
    $form = parent::buildForm($form, $form_state);

    $form['families'] = [
      '#title' => $this->t('Families to Include.'),
      '#description' => $this->t('Select the icon packs to include as libraries. These will be added to every page.'),
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#default_value' => $config->get('families') ?? [],
      '#options' => $this->getFontFamilies(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $families = array_values(array_filter($form_state->getValue('families')));
    $this->configFactory->getEditable('material_icons.settings')
      ->set('families', $families)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
