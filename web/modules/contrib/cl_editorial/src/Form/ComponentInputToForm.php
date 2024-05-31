<?php

namespace Drupal\cl_editorial\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;
use SchemaForms\FormGeneratorInterface;
use Shaper\Util\Context;

class ComponentInputToForm {

  use StringTranslationTrait;

  /**
   * Creates the object.
   *
   * @param \Drupal\sdc\ComponentPluginManager $componentManager
   *   The component manager.
   * @param \SchemaForms\FormGeneratorInterface $formGenerator
   *   The form generator.
   */
  public function __construct(
    protected readonly ComponentPluginManager $componentManager,
    protected readonly FormGeneratorInterface $formGenerator
  ) {}

  /**
   * Generate a form for mapping props and slots into user input.
   *
   * @param string $selected_component
   *   The plugin ID of the selected component.
   * @param mixed $current_input
   *   The currently stored input.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string[] $supported_token_types
   *
   * @return array
   *   The form with the mappings.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  function buildForm(
    string $selected_component,
    array $current_input,
    array $form,
    FormStateInterface $form_state,
    array $supported_token_types = []
  ) {
    $component = $this->componentManager->find($selected_component);
    $schema = $this->getComponentSchema($component);

    $context = new Context([
      'form' => $form,
      'form_state' => $form_state,
      'current_input' => $current_input['props'] ?? [],
    ]);
    $element['props'] = $this->formGenerator->transform($schema, $context);
    // Next, let's add form elements for slots.
    foreach ($component->metadata->slots as $slot => $slot_info) {
      $current_format = $current_input['slots'][$slot]['format'] ?? NULL;
      $current_value = $current_input['slots'][$slot]['value'] ?? NULL;
      $element['slots'][$slot] = [
        '#type' => 'text_format',
        '#title' => $this->t(
          'Slot: @name',
          ['@name' => $slot_info['title'] ?? $slot]
        ),
        '#format' => $current_format,
        '#default_value' => $current_value,
      ];
    }
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $supported_token_types,
      ];
    }

    return $element;
  }

  /**
   * Get the component schema based on the component ID.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   *
   * @return mixed
   *   The schema.
   */
  protected function getComponentSchema(Component $component): mixed {
    // Get the component based on the ID, so we can get the schema.
    $prop_schema = $component->metadata->schema;
    // Encode & decode, so we transform an associative array to an stdClass
    // recursively.
    try {
      $schema = json_decode(
        json_encode($prop_schema, JSON_THROW_ON_ERROR),
        FALSE,
        512,
        JSON_THROW_ON_ERROR
      );
    }
    catch (\JsonException $e) {
      $schema = (object) [];
    }

    return $schema;
  }

}
