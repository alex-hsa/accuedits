<?php

declare(strict_types=1);

namespace Drupal\sdc_display\Plugin\field_group\FieldGroupFormatter;

use Drupal\cl_editorial\Form\ComponentInputToForm;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\Plugin\Component;
use Drupal\sdc\Utilities;
use Drupal\sdc_display\Form\FieldGroupMappingsSettings;
use Drupal\sdc_display\Util;
use SchemaForms\FormGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'details' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "sdc_display",
 *   label = @Translation("Single Directory Component"),
 *   description = @Translation("Map the fields in the group to render in a
 *   single directory component."), supported_contexts = {"view"}
 * )
 */
final class SingleDirectoryComponent extends FieldGroupFormatterBase implements ContainerFactoryPluginInterface {

  public function __construct(
    $plugin_id,
    $plugin_definition,
    \stdClass $group,
    array $settings,
    $label,
    protected RendererInterface $renderer,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ComponentPluginManager $componentPluginManager,
    protected FormGeneratorInterface $formGenerator,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $group, $settings, $label);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['group'],
      $configuration['settings'],
      $configuration['label'],
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.sdc'),
      $container->get('cl_editorial.form_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {
    $settings = $this->getSetting('sdc_field_group');
    $component_id = $settings['component']['machine_name'] ?? '';
    if (!$component_id) {
      return;
    }
    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    $entity = $processed_object['#' . $processed_object['#entity_type']] ?? NULL;
    $mappings = $settings['mappings'] ?? [];
    if (!$component_id) {
      return;
    }
    try {
      $component = $this->componentPluginManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return;
    }
    $prop_values = Util::computePropValues(
      array_keys($component->metadata->schema['properties'] ?? []),
      $mappings['static']['props'] ?? [],
      $mappings['dynamic']['props'] ?? [],
      $element,
      $entity,
      $this->renderer,
    );
    $slot_values = Util::computeSlotValues(
      array_keys($component->metadata->slots),
      $mappings['static']['slots'] ?? [],
      $mappings['dynamic']['slots'] ?? [],
      $element,
      $entity,
    );
    $children = Element::children($element);
    foreach ($children as $child) {
      unset($element[$child]);
    }
    array_map(
      static function(string $child) use ($element) {
        unset($element[$child]);
      },
      $children,
    );
    $element['component'] = [
      '#type' => 'component',
      '#component' => $component_id,
      '#props' => $prop_values,
      '#slots' => $slot_values,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $this->process($element, $rendering_object);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    // This is very weird, but it's a workaround on a Field Group bug. The
    // calling code passes the form and form state into the method, but the
    // signature in the interface is empty.
    $args = func_get_args();
    $form = $args[0] ?? [];
    $form_state = $args[1] ?? NULL;
    $element = parent::settingsForm($form, $form_state);
    $element['sdc_field_group'] = [];
    if (!$form_state) {
      $element['sdc_field_group'] = [
        '#markup' => $this->t('<p><strong>NOTE:</strong> Due to some limitations on how the Field Group modules builds settings forms you need to configure the Single Directory Components settings in the <em>manage display</em> page.</p>'),
      ];
      return $element;
    }
    $default_mappings = [
      'static' => ['props' => [], 'slots' => []],
      'dynamic' => ['props' => [], 'slots' => []],
    ];
    $default_component = ['machine_name' => ''];
    $stored_values = $this->getSetting('sdc_field_group') ?? [
      'component' => $default_component,
      'mappings' => $default_mappings,
    ];
    // The form fingerprint is used to identify which, of the multiple forms in
    // the page, the AJAX response should update.
    try {
      $form_fingerprint = substr(Crypt::hashBase64(sprintf(
        '%s%s',
        $element['#form_id'] ?? '',
        json_encode($element['#parents'] ?? [], JSON_THROW_ON_ERROR),
      )), 0, 6);
      $component_to_form = new ComponentInputToForm(
        $this->componentPluginManager,
        $this->formGenerator,
      );
      $form_mappings = new FieldGroupMappingsSettings(
        $this->componentPluginManager,
        $component_to_form,
        $this->entityFieldManager,
        $this->group->children ?? []
      );
      $name = $this->group->group_name ?? '';
      $form_mappings->alter(
        $element['sdc_field_group'],
        $form_state,
        $name,
        $stored_values,
        $form_fingerprint,
      );
    }
    catch (\JsonException|ComponentNotFoundException $e) {
      // Intentionally left blank.
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $component_id = $this->getSetting('sdc_field_group')['component']['machine_name'] ?? NULL;
    if ($component_id) {
      $summary[] = $this->t(
        'Field group renders using a component: %component_id',
        ['%component_id' => $component_id],
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'sdc_field_group' => [
        'component' => ['machine_name' => ''],
        'mappings' => [
          'static' => ['props' => [], 'slots' => []],
          'dynamic' => ['props' => [], 'slots' => []],
        ],
      ],
      ...parent::defaultSettings($context),
    ];
  }

}
