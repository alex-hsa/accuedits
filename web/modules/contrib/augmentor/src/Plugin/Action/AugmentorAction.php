<?php

namespace Drupal\augmentor\Plugin\Action;

use Drupal\augmentor\AugmentorManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Augmentor Action plugin.
 *
 * @Action(
 *   id = "entity:augmentor_action",
 *   action_label = @Translation("Augmentor"),
 *   deriver = "Drupal\augmentor\Plugin\Action\Derivative\AugmentorActionDeriver",
 * )
 */
class AugmentorAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The augmentor plugin manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, AugmentorManager $augmentor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->augmentorManager = $augmentor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.augmentor.augmentors')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $augmentor = $this->augmentorManager->getAugmentor($this->configuration['augmentor']);
    if (!$augmentor) {
      return;
    }

    $input = $this->getInputFromSourceFields($object);
    $result = $augmentor->execute($input);

    $this->processTargetFields($object, $result);
    $object->save();
  }

  /**
   * Retrieves the input from the source fields.
   *
   * @param object $object
   *   The object to retrieve the input from.
   *
   * @return string
   *   The concatenated input from the source fields.
   */
  protected function getInputFromSourceFields($object) {
    $source_fields = $this->configuration['source_fields'];
    $input = '';

    foreach ($source_fields as $source_field) {
      if ($object->hasField($source_field)) {
        $input .= $object->get($source_field)->getString();
      }
    }

    return $input;
  }

  /**
   * Processes the target fields with the augmentor result.
   *
   * @param object $object
   *   The object to update the target fields on.
   * @param array $result
   *   The result from the augmentor.
   */
  protected function processTargetFields($object, array $result) {
    $targets = $this->configuration['targets'];

    foreach ($targets as $target) {
      $target_field = $target['target_field'];
      $key = $target['key'];

      if ($object->hasField($target_field) && !empty($result) && array_key_exists($key, $result)) {
        $field_definition = $object->get($target_field)->getFieldDefinition();
        $field_type = $field_definition->getType();

        if (in_array($field_type, ['text_long', 'text_with_summary'])) {
          $new_result = [
            'value' => $result[$key],
            'format' => $this->configuration['text_format'],
          ];
          $object->set($target_field, $new_result);
        }
        elseif ($field_type === 'entity_reference' && $this->configuration['explode_separator']) {
          $new_result = explode($this->configuration['explode_separator'], reset($result[$key]));
          $new_result = array_map('trim', $new_result);
          $object->set($target_field, $new_result);
        }
        else {
          $object->set($target_field, $result[$key]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_fields' => [],
      'targets' => [],
      'augmentor' => NULL,
      'action' => NULL,
      'text_format' => NULL,
      'explode_separator' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type = $form['type']['#value'] ?? 'node';
    $allowed_fields = $this->getAllowedFields($entity_type);
    $form['source_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Source fields'),
      '#options' => $allowed_fields ?? [],
      '#default_value' => $this->configuration['source_fields'] ?? '',
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];

    if (!empty($this->configuration['targets'])) {
      $saved_num_targets = count($this->configuration['targets']);
    }

    $num_targets = $form_state->get('num_targets');
    if ($num_targets === NULL) {
      $num_targets = $saved_num_targets ?? 1;
    }
    $form_state->set('num_targets', $num_targets);

    $form['#tree'] = TRUE;
    $form['targets'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advance targeting'),
      '#prefix' => '<div id="targets-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['targets']['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Map the target field and the response key values.'),
    ];
    $targets = $this->configuration['targets'];

    for ($i = 0; $i < $num_targets; $i++) {
      $form['targets'][$i] = [
        '#type' => 'fieldset',
      ];
      $form['targets'][$i]['target_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Target field'),
        '#options' => $allowed_fields ?? [],
        '#default_value' => $targets[$i]['target_field'] ?? '',
      ];

      $form['targets'][$i]['key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Response key to use'),
        '#default_value' => $targets[$i]['key'] ?? 'default',
      ];
    }

    $form['targets']['actions'] = [
      '#type' => 'actions',
    ];

    $form['targets']['actions']['add_target'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more target'),
      '#submit' => ['\Drupal\augmentor\Plugin\Action\AugmentorAction::addOne'],
      '#ajax' => [
        'callback' => [
          $this,
          '\Drupal\augmentor\Plugin\Action\AugmentorAction::addmoreCallback',
        ],
        'wrapper' => 'targets-fieldset-wrapper',
      ],
    ];

    if ($num_targets > 1) {
      $form['targets']['actions']['remove_target'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove last target'),
        '#submit' => ['\Drupal\augmentor\Plugin\Action\AugmentorAction::removeCallback'],
        '#ajax' => [
          'callback' => [
            $this,
            '\Drupal\augmentor\Plugin\Action\AugmentorAction::addmoreCallback',
          ],
          'wrapper' => 'targets-fieldset-wrapper',
        ],
      ];
    }

    $form['augmentor'] = [
      '#type' => 'select',
      '#title' => $this->t('Augmentor'),
      '#options' => $this->getAugmentorOptions(),
      '#default_value' => $this->configuration['augmentor'] ?? '',
      '#required' => TRUE,
    ];
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action over target field'),
      '#options' => [
        'append' => $this->t('Append'),
        'replace' => $this->t('Replace'),
      ],
      '#default_value' => $this->configuration['action'] ?? '',
      '#required' => TRUE,
    ];
    $text_formats = filter_formats(\Drupal::currentUser());
    $text_format_options = [];
    foreach ($text_formats as $text_format) {
      $text_format_options[$text_format->id()] = $text_format->label();
    }
    $form['text_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text Format'),
      '#options' => $text_format_options,
      '#default_value' => $this->configuration['text_format'] ?? '',
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['explode_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Explode separator'),
      '#default_value' => $this->configuration['explode_separator'] ?? '',
      '#size' => 10,
      '#description' => $this->t('Split augmentor response into an array using this separator.'),
    ];
    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the targets in it.
   */
  public static function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['targets'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addOne(array &$form, FormStateInterface $form_state) {
    $num_targets = $form_state->get('num_targets');
    $form_state->set('num_targets', ++$num_targets);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeCallback(array &$element, FormStateInterface $form_state) {
    $num_targets = $form_state->get('num_targets');
    $form_state->set('num_targets', --$num_targets);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $targets = $form_state->getValue('targets');
    unset($targets['actions']);
    $this->configuration['source_fields'] = $form_state->getValue('source_fields');
    $this->configuration['targets'] = $targets;
    $this->configuration['augmentor'] = $form_state->getValue('augmentor');
    $this->configuration['action'] = $form_state->getValue('action');
    $this->configuration['text_format'] = $form_state->getValue('text_format');
    $this->configuration['explode_separator'] = $form_state->getValue('explode_separator');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Returns the list of available augmentors.
   *
   * @return array
   *   The list of available augmentors.
   */
  private function getAugmentorOptions(): array {
    $options = [];
    foreach ($this->augmentorManager->getAugmentors() as $uuid => $augmentor) {
      $options[$uuid] = $augmentor['label'];
    }
    return $options;
  }

  /**
   * Returns the allowed fields for Augmentor configuration.
   *
   * @param string $entity_type
   *   The type of entity.
   *
   * @return array
   *   The list of allowed fields for Augmentor configuration.
   */
  private function getAllowedFields(string $entity_type): array {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    $field_names = array_keys($fields);
    $allowed_fields = [];
    foreach ($field_names as $field_name) {
      if ($field_name == 'body' || $field_name == 'title' || str_starts_with($field_name, 'field_')) {
        $label = str_replace('field_', '', $field_name);
        $allowed_fields[$field_name] = ucwords(str_replace('_', ' ', $label));
      }
    }
    return $allowed_fields;
  }

}
