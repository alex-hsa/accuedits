<?php

namespace Drupal\sdc_display\Form;

use Drupal\cl_editorial\Form\ComponentInputToForm;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
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
class ViewModeMappingsSettings extends BaseMappingsSettings {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ComponentPluginManager $componentManager,
    ComponentInputToForm $componentToForm,
    protected readonly EntityFieldManagerInterface $entityFieldManager
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
    $input_name = ':input[name="sdc_display[enabled]"]';
    $this->getComponentSelector(
      $form,
      $input_name,
      $stored_values,
      'sdc_display:view_mode',
      $form_fingerprint,
    );
    $selected_component = $this->getCurrentlySelectedComponent(
      $form_state,
      $stored_values['component']['machine_name'] ?? NULL,
      ['sdc_display', 'component']
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
        'enabled' => t('Enable this to render this view mode using a component. All individual fields will be rendered as usual, but instead of placing them in a stack (one on top of the other), you will be able to assign fields to the component\'s inputs (props/slots).'),
        'component' => t('Select the component you want to use to render this view mode.'),
      ]
    );
  }

  /**
   * Submit handler to save the view mode settings as a third party option.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submit(array &$form, FormStateInterface $form_state): void {
    // Hey! I give up here. Whenever there are existing values in the form for the
    // view mode populated from the default values, AND a formatter settings form
    // is saved (not necessarily setting component stuff). Then upon saving the
    // view display form we don't get the values for the mappings when using
    // $form_state->getValue('sdc_display'). Therefore, we'll use the raw user
    // input.
    $raw_user_input = $form_state->getUserInput();
    $values = NestedArray::getValue($raw_user_input, [
      ...$form['#array_parents'],
      'sdc_display',
    ]);
    // If the integration is disabled, delete the mappings.
    $default_mappings = [
      'static' => ['props' => [], 'slots' => []],
      'dynamic' => ['props' => [], 'slots' => []],
    ];
    $default_component = ['machine_name' => ''];
    if (!($values['enabled'] ?? FALSE)) {
      $values['component'] = $default_component;
      $values['mappings'] = $default_mappings;
    }
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof EntityFormInterface);
    $entity = $form_object->getEntity();
    assert($entity instanceof EntityDisplayInterface);
    $entity->setThirdPartySetting(
      'sdc_display',
      'enabled',
      $values['enabled'] ?? '0',
    );
    $entity->setThirdPartySetting(
      'sdc_display',
      'component',
      $values['component'] ?? $default_component,
    );
    $entity->setThirdPartySetting(
      'sdc_display',
      'mappings',
      $values['mappings'] ?? $default_mappings,
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
      '#description' => t('Only fields enabled in the previous table will be allowed in the mappings below. If you are missing a field, make sure to place it outside of the <em>Disabled</em> section.'),
      '#attached' => ['library' => ['sdc_display/admin']],
    ];
    if (!empty($prop_mapping_elements)) {
      $element['props'] = [
        '#type' => 'fieldset',
        '#title' => t('Props'),
        '#description' => t('Select, at most, one field per prop. When rendering the entity, the contents of the field will be passed to the prop.'),
        '#description_display' => 'before',
        ...$prop_mapping_elements,
      ];
    }
    if (!empty($slot_mapping_elements)) {
      $element['slots'] = [
        '#type' => 'fieldset',
        '#title' => t('Slots'),
        '#description' => t('Select any number of fields per slot. When rendering the entity, the contents of the fields will be rendered into the slot. The fields will be sorted based on order set previously.'),
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
   * @param $default_values
   *   The default values.
   *
   * @return array
   *   The form element.
   */
  protected function dynamicMappingsPropsForm(
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
        '#title' => $schema['title'] ?? ucwords(strtr($prop, ['_' => ' ', '-' => ' '])),
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
  protected function dynamicMappingsSlotsForm(
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
        '#title' => $info['title'] ?? ucwords(strtr($slot, ['_' => ' ', '-' => ' '])),
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
  protected function getDynamicMappingsSelectorOptions(FormStateInterface $form_state): array {
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
    foreach (array_keys($view_display->getComponents()) as $field_name) {
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
