<?php

namespace Drupal\augmentor\Plugin\Field\FieldWidget;

use Drupal\augmentor\AugmentorManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for the Augmentor field widgets.
 */
abstract class AugmentorBaseWidget extends TextareaWidget implements ContainerFactoryPluginInterface {

  /**
   * The augmentor plugin manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Construct a AugmentorBaseWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Defines an interface for entity field definitions.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor plugin manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AugmentorManager $augmentor_manager, EntityStorageInterface $file_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->augmentorManager = $augmentor_manager;
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.augmentor.augmentors'),
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (array_search('default_value_input', $element['#field_parents']) !== FALSE || empty($this->getSetting('augmentor'))) {
      return;
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $augmentor_field_name = 'execute_' . $this->fieldDefinition->getName();
    $source_fields = $this->getSetting('source_fields');
    $entity = $items->getEntity();
    $source_fields_values = [];
    $type = 'dynamic';

    foreach ($source_fields as $field_name) {
      $field_type = $entity->get($field_name)->getFieldDefinition()->getType();

      $source_fields_values[$field_name] = [
        $field_type => [
          'value' => '',
        ],
      ];

      if ($field_type != 'entity_reference' && $field_type != 'entity_reference_revisions' && !$entity->get($field_name)->isEmpty()) {
        $values = $entity->get($field_name)->getValue();

        foreach ($values as $value) {
          switch ($field_type) {
            case 'image':
            case 'file':
              $fid = $value['target_id'];
              $file = $this->fileStorage->load($fid);
              $value = $file->getFileUri();
              $type = 'static';
              break;

            default:
              break;
          }

          $source_fields_values[$field_name][$field_type] = $value;
        }
      }
    }

    $button_label = $this->getSetting('button_label');
    $augmentor = $this->getSetting('augmentor');
    $targets = $this->getSetting('targets');
    // Remove actions config.
    unset($targets['actions']);
    $action = $this->getSetting('action');
    $explode_separator = $this->getSetting('explode_separator');

    $url = Url::fromRoute(
      'augmentor.augmentor_execute',
      [],
      ['absolute' => TRUE],
    )->toString();

    $form['#attached']['library'][] = 'augmentor/augmentor_library';
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name] = [
      'url' => $url,
      'data' => [
        'input' => $this->extractValue($source_fields_values),
        'action' => $action,
        'targets' => $targets,
        'source_fields' => $source_fields_values,
        'augmentor' => $augmentor,
        'explode_separator' => $explode_separator,
        'type' => $type,
      ],
    ];

    $element = [
      'top' => [
        '#type' => 'container',
        '#weight' => 1,
        'execute' => [
          '#type' => 'submit',
          '#value' => $button_label,
          '#name' => $augmentor_field_name,
          '#weight' => 502,
          '#delta' => $delta,
          '#access' => $this->augmentorExecuteAccess($augmentor),
          '#attributes' => [
            'class' => [
              'button button--primary js-form-submit form-submit augmentor-cta-link',
            ],
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'augmentor' => '',
      'source_fields' => [],
      'targets' => [],
      'action' => '',
      'button_label' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $options = [];

    unset($element['rows']);
    unset($element['placeholder']);

    foreach ($form['#fields'] as $field_name) {
      if ($field_name == 'body' || $field_name == 'title' || str_starts_with($field_name, 'field_')) {
        $label = str_replace('field_', '', $field_name);
        $allowed_fields[$field_name] = ucwords(str_replace('_', ' ', $label));
      }
    }

    $element['source_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Source fields'),
      '#options' => $allowed_fields ?? [],
      '#default_value' => $this->getSetting('source_fields'),
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];

    if (!empty($this->getSetting('targets'))) {
      $saved_num_targets = count($this->getSetting('targets')) - 1;
    }

    $num_targets = $form_state->get($this->fieldDefinition->getName() . '_num_targets');

    if ($num_targets === NULL) {
      $num_targets = $saved_num_targets ?? 1;
    }

    $form_state->set($this->fieldDefinition->getName() . '_num_targets', $num_targets);

    $element['#tree'] = TRUE;
    $element['targets'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advance targeting'),
      '#prefix' => '<div id="targets-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['targets']['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Map the target field and the response key values.'),
    ];

    $targets = $this->getSetting('targets');

    for ($i = 0; $i < $num_targets; $i++) {
      $element['targets'][$i] = [
        '#type' => 'fieldset',
      ];
      $element['targets'][$i]['target_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Target field'),
        '#options' => $allowed_fields ?? [],
        '#default_value' => $targets[$i]['target_field'] ?? '',
      ];

      $element['targets'][$i]['key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Response key to use'),
        '#default_value' => $targets[$i]['key'] ?? 'default',
      ];
    }

    $element['targets']['actions'] = [
      '#type' => 'actions',
    ];

    $element['targets']['actions']['add_target'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more target'),
      '#submit' => ['\Drupal\augmentor\Plugin\Field\FieldWidget\AugmentorBaseWidget::addOne'],
      '#ajax' => [
        'callback' => [
          $this,
          '\Drupal\augmentor\Plugin\Field\FieldWidget\AugmentorBaseWidget::addmoreCallback',
        ],
        'wrapper' => 'targets-fieldset-wrapper',
      ],
    ];

    if ($num_targets > 1) {
      $element['targets']['actions']['remove_target'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove last target'),
        '#submit' => ['\Drupal\augmentor\Plugin\Field\FieldWidget\AugmentorBaseWidget::removeCallback'],
        '#ajax' => [
          'callback' => [
            $this,
            '\Drupal\augmentor\Plugin\Field\FieldWidget\AugmentorBaseWidget::addmoreCallback',
          ],
          'wrapper' => 'targets-fieldset-wrapper',
        ],
      ];
    }

    foreach ($this->augmentorManager->getAugmentors() as $uuid => $augmentor) {
      $options[$uuid] = $augmentor['label'];
    }

    $element['augmentor'] = [
      '#type' => 'select',
      '#title' => $this->t('Augmentor'),
      '#options' => $options,
      '#default_value' => $this->getSetting('augmentor'),
      '#required' => TRUE,
    ];

    $element['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action over target field text'),
      '#options' => [
        'append' => $this->t('Append'),
        'preppend' => $this->t('Preppend'),
        'replace' => $this->t('Replace'),
      ],
      '#default_value' => $this->getSetting('action'),
      '#required' => TRUE,
    ];

    $element['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->getSetting('button_label'),
      '#size' => 60,
      '#description' => $this->t('Label of the button to execute the augmentor'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($augmentor = $this->getSetting('augmentor')) {
      $summary[] = $this->t('Augmentor name: @name', ['@name' => $augmentor]);
    }

    if ($source = $this->getSetting('source_fields')) {
      $summary[] = $this->t('Source fields: @source', ['@source' => implode(', ', $source)]);
    }

    if ($target = $this->getSetting('targets')) {
      $summary[] = $this->t('Target fields: @target', ['@target' => json_encode($target)]);
    }

    if ($action = $this->getSetting('action')) {
      $summary[] = $this->t('Action: @action', ['@action' => $action]);
    }

    if ($button_label = $this->getSetting('button_label')) {
      $summary[] = $this->t('Button label: @label', ['@label' => $button_label]);
    }

    return $summary;
  }

  /**
   * Access callback for the execute button.
   *
   * @param string $augmentor_id
   *   The augmentor plugin id.
   *
   * @return bool
   *   TRUE if the augmentor exists, FALSE otherwise.
   */
  protected function augmentorExecuteAccess($augmentor_id) {
    $augmentor = $this->augmentorManager->getAugmentor($augmentor_id);

    if (isset($augmentor)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Extract the value of the given input depending on the type.
   *
   * @param string $input
   *   The input to process.
   *
   * @return string
   *   The raw value of the input.
   */
  protected function extractValue($input) {
    $value = '';
    foreach ($input as $field) {
      $type = key($field);

      switch ($type) {
        case 'image':
        case 'file':
          $value .= $field[$type];
          break;

        default:
          $value .= $field[$type]['value'] . "\n\n";
          break;
      }
    }

    return strip_tags($value);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the targets in it.
   */
  public static function addmoreCallback(array &$element, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $field_name = $button['#parents'][1];
    return $element['fields'][$field_name]['plugin']['settings_edit_form']['settings']['targets'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addOne(array &$element, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $field_name = $button['#parents'][1];

    $num_targets = $form_state->get($field_name . '_num_targets');
    $form_state->set($field_name . '_num_targets', ++$num_targets);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeCallback(array &$element, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $field_name = $button['#parents'][1];
    $num_targets = $form_state->get($field_name . '_num_targets');

    if ($num_targets > 1) {
      $form_state->set($field_name . '_num_targets', --$num_targets);
    }
    $form_state->setRebuild();
  }

}
