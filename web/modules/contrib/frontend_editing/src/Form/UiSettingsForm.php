<?php

namespace Drupal\frontend_editing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure frontend_editing settings for this site.
 */
class UiSettingsForm extends ConfigFormBase {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The extension list module.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->extensionListModule = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'frontend_editing_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['frontend_editing.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('frontend_editing.settings');
    $form['ajax_content_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Ajax content update'),
      '#description' => $this->t('When enabled, the content after editing or adding entities will be updated via Ajax instead of a page refresh.'),
      '#default_value' => $config->get('ajax_content_update'),
    ];
    if ($config->get('ajax_content_update')) {
      $this->messenger()->addStatus($this->t('Ajax content update is enabled.'));
    }

    // Preview of the hover highlight mode.
    $form['hover_highlight_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['frontend-editing-hover-highlight-preview'],
      ],
    ];
    $form['hover_highlight_preview']['hover_highlight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Hover Highlight Mode'),
      '#description' => $this->t('Toggle between a subtle border or a more distinct margin and background highlight for editable paragraphs on hover.'),
      '#default_value' => $config->get('hover_highlight'),
    ];
    $form['hover_highlight_preview']['disabled'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="hover_highlight"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['hover_highlight_preview']['disabled']['image'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $this->fileUrlGenerator->generateAbsoluteString($this->extensionListModule->getPath('frontend_editing') . '/images/hover_highlight_disabled.png'),
        'alt' => $this->t('Hover Highlight Preview'),
        'width' => '400px',
        'height' => 'auto',
      ],
    ];
    $form['hover_highlight_preview']['enabled'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="hover_highlight"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['hover_highlight_preview']['enabled']['image'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $this->fileUrlGenerator->generateAbsoluteString($this->extensionListModule->getPath('frontend_editing') . '/images/hover_highlight_enabled.png'),
        'alt' => $this->t('Hover Highlight Preview'),
        'width' => '400px',
        'height' => 'auto',
      ],
    ];

    // Color picker field for the primary color.
    $form['primary_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Primary UI Color'),
      '#default_value' => $config->get('primary_color'),
      '#description' => $this->t('Choose the primary color for the frontend editing interface. Be sure to choose a color that has enough contrast with the background color of your site.'),
    ];

    // Enable on/off toggle in UI.
    $form['ui_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable on/off toggle in UI'),
      '#description' => $this->t('When enabled, the user will be able to toggle the frontend editing on and off via a button in the UI.'),
      '#default_value' => $config->get('ui_toggle'),
    ];

    // Wrapper for UI Toggle settings.
    $form['ui_toggle_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('UI Toggle Settings'),
      '#open' => $config->get('ui_toggle'),
      '#description' => $this->t('Configure the UI Toggle settings.'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="ui_toggle"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $offsets = [
      'top' => 'Top',
      'right' => 'Right',
      'bottom' => 'Bottom',
      'left' => 'Left',
    ];

    foreach ($offsets as $key => $label) {
      $form['ui_toggle_settings']["offset_$key"] = [
        '#type' => 'number',
        '#title' => $this->t("Offset @label", ['@label' => $label]),
        '#description' => $this->t("Define the offset @label from the selected position. (px)", ['@label' => $label]),
        '#default_value' => $config->get("ui_toggle_settings.offset_$key"),
      ];
    }

    // Set fields to exclude from ajax content update.
    $exclude_fields = $config->get('exclude_fields') ? implode("\r\n", $config->get('exclude_fields')) : '';
    $form['exclude_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Do not modify the following fields for ajax content update:'),
      '#description' => $this->t('List of fields of type entity reference/entity reference (revisions) that should not have an additional wrapper used for ajax content update. Use this setting in case your template is sensitive to markup or you get some properties directly from field render array variables. Put one field per line. Format: entity_type.bundle.field_name'),
      '#default_value' => $exclude_fields,
      '#states' => [
        'visible' => [
          ':input[name="ajax_content_update"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $message = $this->t('When using ajax content update the html markup of entity reference (revisions) fields is changed slightly to have the reliable target for injecting the updated content. Potentially this can break the HTML on the page, therefor use "Exclude fields" setting or disable ajax content update completely if you experience any issues.');
    $this->messenger()->addWarning($message);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('frontend_editing.settings');
    $config->set('ajax_content_update', (bool) $form_state->getValue('ajax_content_update'));
    $config->set('hover_highlight', (bool) $form_state->getValue('hover_highlight'));
    $config->set('primary_color', $form_state->getValue('primary_color'));
    $config->set('ui_toggle', (bool) $form_state->getValue('ui_toggle'));
    $config->set('ui_toggle_settings', $form_state->getValue('ui_toggle_settings'));
    $exclude_fields = $form_state->getValue('exclude_fields');
    $exclude_fields = explode("\r\n", $exclude_fields);
    $exclude_fields = array_filter($exclude_fields);
    $config->set('exclude_fields', $exclude_fields);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
