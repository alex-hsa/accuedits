<?php

namespace Drupal\sdc_display\Form;

use Drupal\cl_editorial\Form\ComponentInputToForm;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;

/**
 * Form alterations for the mappings form for view modes.
 */
final class FieldGroupMappingsSettings extends BaseMappingsSettings {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ComponentPluginManager $componentManager,
    ComponentInputToForm $componentToForm,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly array $fieldGroupChildren
  ) {
    parent::__construct($componentManager, $componentToForm);
  }

  /**
   * Creates a form for selecting a component and mappings for view modes.
   *
   * @param array $form
   *   The form array to fill.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $name
   *   The name.
   * @param array $stored_values
   *   Previously stored values for this form. Used to populate default values.
   * @param string $form_fingerprint
   *   An identifier for this form. Used for the AJAX request to know what form
   *   to update when there are multiple on the page.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  public function alter(
    array &$form,
    FormStateInterface $form_state,
    string $name,
    array $stored_values,
    string $form_fingerprint
  ): void {
    $element_parents = [
      'fields',
      $name,
      'settings_edit_form',
      'settings',
      'sdc_field_group',
    ];
    $this->getComponentSelector(
      $form,
      '',
      $stored_values,
      'sdc_display:view_mode',
      $form_fingerprint,
    );
    // We don't need the checkbox to enable the integration here. By adding the
    // SDC field group we are already hinting that.
    unset(
      $form['enabled'],
      $form['component']['#states'],
      $form['mappings']['#states'],
    );
    $form['component']['#required'] = TRUE;
    $selected_component = $this->getCurrentlySelectedComponent(
      $form_state,
      $stored_values['component']['machine_name'] ?? NULL,
      [...$element_parents, 'component']
    );
    if ($selected_component) {
      $form['mappings']['static'] = $this->staticMappingsForm(
        $form,
        $form_state,
        $selected_component,
        $stored_values['mappings']['static'] ?? []
      );
      $form['mappings']['dynamic'] = $this->dynamicMappingsForm(
        $selected_component,
        $stored_values['mappings']['dynamic'] ?? [],
        $form_state
      );
    }
    $this->setDescriptionTexts(
      $form,
      [
        'component' => t('Select the component you want to use to render this field group.'),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function dynamicMappingsForm(
    string $component_id,
    $default_value,
    FormStateInterface $form_state
  ): array {
    try {
      $component = $this->componentManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return ['#markup' => t('<em>Unable to find component.</em>')];
    }

    $prop_mapping_elements = $this->dynamicMappingsPropsForm($component, $form_state, $default_value['props'] ?? []);
    $slot_mapping_elements = $this->dynamicMappingsSlotsForm($component, $form_state, $default_value['slots'] ?? []);
    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Component Prop/Slot Mapping'),
      '#tree' => TRUE,
      '#description' => t('Only the fields in the field group will be allowed in the mappings below. If you are missing a field, make sure to add it to the field group.'),
      '#attached' => ['library' => ['sdc_display/admin']],
    ];
    if (!empty($prop_mapping_elements)) {
      $element['props'] = [
        '#type' => 'fieldset',
        '#title' => t('Props'),
        '#description' => t('Select, at most, one field per prop. When rendering the field group, the contents of the field will be passed to the prop.'),
        '#description_display' => 'before',
        ...$prop_mapping_elements,
      ];
    }
    if (!empty($slot_mapping_elements)) {
      $element['slots'] = [
        '#type' => 'fieldset',
        '#title' => t('Slots'),
        '#description' => t('Select any number of fields per slot. When rendering the field group, the contents of the fields will be rendered into the slot. The fields will be sorted based on order set in the group.'),
        '#description_display' => 'before',
        ...$slot_mapping_elements,
      ];
    }
    return $element;
  }

  /**
   * Builds the form element for the dynamic mappings for props.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $default_values
   *   The default values.
   *
   * @return array
   *   The form element.
   */
  private function dynamicMappingsPropsForm(
    Component $component,
    FormStateInterface $form_state,
    array $default_values,
  ): array {
    $mapping_selector_options = [
      NULL => t('- Not mapped -'),
      ...$this->getDynamicMappingsSelectorOptions($form_state),
    ];
    $elements = [];
    $prop_schemas = $component->metadata->schema['properties'] ?? [];
    foreach ($prop_schemas as $prop => $schema) {
      $elements[$prop] = [
        '#type' => 'radios',
        '#title' => $schema['title'] ?? ucwords(
          strtr($prop, ['_' => ' ', '-' => ' ']),
        ),
        '#description' => $schema['description'] ?? NULL,
        '#options' => $mapping_selector_options,
        '#default_value' => $default_values[$prop] ?? NULL,
      ];
    }
    return $elements;
  }

  /**
   * Builds the form element for the dynamic mappings for slots.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $default_values
   *   The default values.
   *
   * @return array
   *   The form element.
   */
  private function dynamicMappingsSlotsForm(
    Component $component,
    FormStateInterface $form_state,
    array $default_values,
  ): array {
    $mapping_selector_options = $this->getDynamicMappingsSelectorOptions($form_state);
    $elements = [];
    $slots = $component->metadata->slots ?? [];
    foreach ($slots as $slot => $info) {
      $elements[$slot] = [
        '#type' => 'checkboxes',
        '#title' => $schema['title'] ?? ucwords(strtr(
          $slot,
          [
            '_' => ' ',
            '-' => ' ',
          ],
        )),
        '#description' => $schema['description'] ?? NULL,
        '#options' => $mapping_selector_options,
        '#default_value' => array_keys(
          array_filter($default_values[$slot] ?? [])
        ),
      ];
    }
    return $elements;
  }

  /**
   * Builds the options for the select/checkboxes in the mappings forms.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The options for the Form API elements.
   */
  private function getDynamicMappingsSelectorOptions(FormStateInterface $form_state): array {
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof EntityFormInterface);
    $view_display = $form_object->getEntity();
    assert($view_display instanceof EntityViewDisplayInterface);
    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions(
        $view_display->getTargetEntityTypeId(),
        $view_display->getTargetBundle()
      );
    $mapping_selector_options = [];
    foreach ($this->fieldGroupChildren as $field_name) {
      $mapping_selector_options[$field_name] = $field_name;
      // If entity field exists, then get the label from it.
      if (isset($field_definitions[$field_name])) {
        $field_definition = $field_definitions[$field_name];
        $mapping_selector_options[$field_name] = $field_definition->getLabel();
      }
    }
    return $mapping_selector_options;
  }

}
