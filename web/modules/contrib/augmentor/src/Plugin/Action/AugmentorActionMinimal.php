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
 * Augmentor Minimal Action plugin.
 *
 * @Action(
 *   id = "entity:augmentor_action_minimal",
 *   action_label = @Translation("Augmentor Minimal"),
 *   deriver = "Drupal\augmentor\Plugin\Action\Derivative\AugmentorActionDeriver",
 * )
 */
class AugmentorActionMinimal extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

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
    $input = '';

    for ($i = 1; $i <= 10; $i++) {
      $source_field_key = 'source_field_' . $i;
      if ($this->configuration[$source_field_key]) {
        $source_field = $this->configuration[$source_field_key];
        if ($object->hasField($source_field)) {
          $input .= $object->get($source_field)->getString();
        }
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
    for ($i = 1; $i <= 10; $i++) {
      $target_field_key = 'target_field_' . $i;
      $response_field_key = 'response_key_' . $i;

      if ($this->configuration[$target_field_key] && $this->configuration[$response_field_key]) {
        $target_field = $this->configuration[$target_field_key];
        $response_field = $this->configuration[$response_field_key];

        if ($object->hasField($target_field) && !empty($result) && array_key_exists($response_field, $result)) {
          $field_definition = $object->get($target_field)->getFieldDefinition();
          $field_type = $field_definition->getType();

          if (in_array($field_type, ['text_long', 'text_with_summary'])) {
            $new_result = [
              'value' => $result[$response_field],
              'format' => $this->configuration['text_format'],
            ];
            $object->set($target_field, $new_result);
          }
          elseif ($field_type === 'entity_reference' && $this->configuration['explode_separator']) {
            $new_result = explode($this->configuration['explode_separator'], reset($result[$response_field]));
            $new_result = array_map('trim', $new_result);
            $object->set($target_field, $new_result);
          }
          else {
            $object->set($target_field, $result[$response_field]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $fields = [];
    for ($i = 1; $i <= 10; $i++) {
      $source_field_key = 'source_field_' . $i;
      $target_field_key = 'target_field_' . $i;
      $response_field_key = 'response_key_' . $i;
      $fields[$source_field_key] = NULL;
      $fields[$target_field_key] = NULL;
      $fields[$response_field_key] = NULL;
    }
    return $fields + [
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
    for ($i = 1; $i <= 10; $i++) {
      $source_field_key = 'source_field_' . $i;
      $source_field_title = 'Source Field ' . $i;
      $form[$source_field_key] = [
        '#type' => 'select',
        '#title' => $source_field_title,
        '#options' => $allowed_fields ?? [],
        '#default_value' => $this->configuration[$source_field_key] ?? '',
        '#empty_option' => $this->t('- Select -'),
        '#required' => FALSE,
      ];
      // Make first row of the fields mandatory.
      if ($i === 1) {
        unset($form[$source_field_key]['#empty_option']);
        $form[$source_field_key]['#required'] = TRUE;
      }
    }
    for ($i = 1; $i <= 10; $i++) {
      $target_field_key = 'target_field_' . $i;
      $target_field_title = 'Target Field ' . $i;
      $response_field_key = 'response_key_' . $i;
      $response_field_title = 'Response key to use ' . $i;
      $form[$target_field_key] = [
        '#type' => 'select',
        '#title' => $target_field_title,
        '#options' => $allowed_fields ?? [],
        '#default_value' => $this->configuration[$target_field_key] ?? '',
        '#empty_option' => $this->t('- Select -'),
        '#required' => FALSE,
      ];
      $form[$response_field_key] = [
        '#type' => 'textfield',
        '#title' => $response_field_title,
        '#default_value' => $this->configuration[$response_field_key] ?? '',
        '#empty_option' => $this->t('- Select -'),
        '#required' => FALSE,
      ];
      // Make first row of the fields mandatory.
      if ($i === 1) {
        unset($form[$target_field_key]['#empty_option']);
        $form[$target_field_key]['#required'] = TRUE;
        unset($form[$response_field_key]['#empty_option']);
        $form[$response_field_key]['#required'] = TRUE;
        $form[$response_field_key]['#default_value'] = 'default';
      }
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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    for ($i = 1; $i <= 10; $i++) {
      $source_field_key = 'source_field_' . $i;
      $target_field_key = 'target_field_' . $i;
      $response_field_key = 'response_key_' . $i;
      $this->configuration[$source_field_key] = $form_state->getValue($source_field_key);
      $this->configuration[$target_field_key] = $form_state->getValue($target_field_key);
      $this->configuration[$response_field_key] = $form_state->getValue($response_field_key);
    }
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
