<?php

namespace Drupal\responsive_tables_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_tables_filter\Plugin\Filter\FilterResponsiveTablesFilter;

/**
 * Configure settings for the Responsive Tables Filter module.
 */
class TablesawSettings extends ConfigFormBase {

  const SETTINGS = 'responsive_tables_filter.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_tables_filter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['views_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically add to all table-based Drupal Views and tables generated via theme rendering.'),
      '#description' => $this->t('For more fine-grained control of which Views tables should use the Tablesaw library, leave this unchecked and add the table attributes programmatically. See https://github.com/filamentgroup/tablesaw'),
      '#default_value' => $config->get('views_enabled'),
    ];

    $form['views_tablesaw_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Tablesaw mode'),
      '#default_value' => $config->get('views_tablesaw_mode') ?? 'stack',
      '#description' => $this->t('This will apply to all Views-generated tables on the site. See documentation: https://github.com/filamentgroup/tablesaw'),
      '#options' => FilterResponsiveTablesFilter::$modes,
      '#states' => [
        'visible' => [
          ':input[name="views_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('views_enabled', $form_state->getValue('views_enabled'))
      ->set('views_tablesaw_mode', $form_state->getValue('views_tablesaw_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
