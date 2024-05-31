<?php

namespace Drupal\sdc_display\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;

/**
 * Form alterations for the mappings form for field formatters.
 */
class FieldFormatterMappingsSettings extends BaseMappingsSettings {

  /**
   * Creates a form for selecting a component and mappings the field to an
   * input.
   *
   * @param array $form
   *   The form array to fill.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $name
   *   The field name.
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
      'third_party_settings',
      'sdc_display',
    ];
    $input_name = ':input[name="fields[' . implode('][', [
      ...array_slice($element_parents, 1),
      'enabled',
    ]) . ']"]';
    $this->getComponentSelector(
      $form,
      $input_name,
      $stored_values,
      'sdc_display:field_formatter',
      $form_fingerprint,
    );
    $selected_component = $this->getCurrentlySelectedComponent(
      $form_state,
      $stored_values['component']['machine_name'] ?? NULL,
      [...$element_parents, 'component']
    );
    if (!$selected_component) {
      return;
    }
    $form['mappings']['static'] = $this->staticMappingsForm(
      $form,
      $form_state,
      $selected_component,
      $stored_values['mappings']['static'] ?? []
    );
    $form['mappings']['dynamic'] = $this->dynamicMappingsForm(
      $selected_component,
      $stored_values['mappings']['dynamic'] ?? NULL,
      $form_state
    );
    $this->setDescriptionTexts(
      $form,
      [
        'enabled' => t('Enable this to render the field using a component. The field will render as usual, then the result will be passed to a component.'),
        'component' => t('Select the component to render the field.'),
        'mappings.dynamic' => t('The selected prop/slot will receive the result of rendering the field using the selected formatter. If you want to pass the raw data instead, consider the <a href=":url">No Markup</a> module.', [':url' => 'https://www.drupal.org/project/nomarkup']),
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
    $options = [NULL => t('- None -')];
    $prop_schemas = $component->metadata->schema['properties'] ?? [];
    foreach ($prop_schemas as $prop => $schema) {
      $options[t('Props')->__toString()][$prop] = $schema['title'] ?? $prop;
    }
    foreach ($component->metadata->slots as $slot => $slot_info) {
      $options[t('Slots')->__toString()][$slot] = $slot_info['title'] ?? $slot;
    }
    return [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Component Prop/Slot Mapping'),
      '#required' => TRUE,
      'mapped' => [
        '#type' => 'select',
        '#title' => t('This Field Populates...'),
        '#description' => t('Select the prop or slot that the field should populate. If the field is empty, the field value provided in "Fixed Values" will be used.'),
        '#options' => $options,
        '#default_value' => $default_value,
      ],
    ];
  }

}
