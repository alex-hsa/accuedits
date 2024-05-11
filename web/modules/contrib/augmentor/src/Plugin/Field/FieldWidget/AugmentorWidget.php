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
 * An augmentor field widget.
 *
 * @FieldWidget(
 *   id = "augmentor_widget",
 *   label = @Translation("Augmentor"),
 *   field_types = {
 *     "field_augmentor_type",
 *   }
 * )
 */
class AugmentorWidget extends TextareaWidget implements ContainerFactoryPluginInterface {

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
   * Construct a AugmentorWidget object.
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

    foreach ($source_fields as $field_name) {
      $field_type = $entity->get($field_name)->getFieldDefinition()->getType();

      if ($field_type != 'entity_reference' && $field_type != 'entity_reference_revisions' && !$entity->get($field_name)->isEmpty()) {
        $values = $entity->get($field_name)->getValue();

        foreach ($values as $value) {
          switch ($field_type) {
            case 'image':
              $fid = $value['target_id'];
              $file = $this->fileStorage->load($fid);
              $value = $file->createFileUrl(FALSE);
              break;

            case 'datetime':
              // code...
              break;

            default:
              // code...
              break;
          }

          $source_fields_values[$field_name] = [
            $field_type => $value,
          ];
        }
      }
    }

    $button_label = $this->getSetting('button_label');
    $augmentor = $this->getSetting('augmentor');
    $target = $this->getSetting('target_field');
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
        'target' => $target,
        'augmentor' => $augmentor,
        'explode_separator' => $explode_separator,
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
          '#access' => $this->augmentorExecuteAccess(),
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
      'source_fields' => '',
      'target_field' => '',
      'action' => '',
      'button_label' => '',
      'explode_separator' => '',
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

    $element['target_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Target field'),
      '#options' => $allowed_fields ?? [],
      '#default_value' => $this->getSetting('target_field'),
      '#required' => TRUE,
      '#multiple' => FALSE,
    ];

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
      '#title' => $this->t('Action over source field text'),
      '#options' => [
        'append' => $this->t('Append'),
        'preppend' => $this->t('Preppend'),
        'replace' => $this->t('Replace'),
      ],
      '#default_value' => $this->getSetting('action'),
      '#required' => TRUE,
    ];

    $element['explode_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Explode separator'),
      '#default_value' => $this->getSetting('explode_separator'),
      '#size' => 10,
      '#description' => $this->t('Split augmentor response into an array using this separator.'),
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

    if ($target = $this->getSetting('target_field')) {
      $summary[] = $this->t('Target fields: @target', ['@target' => $target]);
    }

    if ($action = $this->getSetting('action')) {
      $summary[] = $this->t('Action: @action', ['@action' => $action]);
    }

    if ($button_label = $this->getSetting('button_label')) {
      $summary[] = $this->t('Button label: @label', ['@label' => $button_label]);
    }

    if ($separator = $this->getSetting('explode_separator')) {
      $summary[] = $this->t('Explode separator: "@separator"', ['@separator' => $separator]);
    }

    return $summary;
  }

  /**
   * Access callback for the execute button.
   */
  protected function augmentorExecuteAccess() {
    return TRUE;
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
          $value .= $field[$type];
          break;

        default:
          $value .= $field[$type]['value'];
          break;
      }
    }

    return strip_tags($value);
  }

}
