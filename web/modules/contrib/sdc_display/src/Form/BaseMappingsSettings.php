<?php

namespace Drupal\sdc_display\Form;

use Drupal\cl_editorial\Form\ComponentInputToForm;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\sdc\ComponentPluginManager;

/**
 * Common form alterations for the mappings form.
 */
abstract class BaseMappingsSettings {

  /**
   * Creates a mappings settings object.
   *
   * @param \Drupal\sdc\ComponentPluginManager $componentManager
   *   The component manager.
   */
  public function __construct(
    protected readonly ComponentPluginManager $componentManager,
    protected readonly ComponentInputToForm $componentToForm
  ) {}

  /**
   * Creates a form for selecting a component and add mappings to it.
   *
   * @param array $form
   *   The form array to fill.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $name
   *   The name of the field or the view mode.
   * @param array $stored_values
   *   Previously stored values for this form. Used to populate default values.
   * @param string $form_fingerprint
   *   An identifier for this form. Used for the AJAX request to know what form
   *   to update when there are multiple on the page.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  abstract public function alter(
    array &$form,
    FormStateInterface $form_state,
    string $name,
    array $stored_values,
    string $form_fingerprint
  );

  /**
   * Generates a form for dynamic mapping of properties.
   *
   * In other words, it will let you choose what prop the field will feed.
   *
   * @param string $component_id
   *   The component ID.
   * @param $default_value
   *   The previously saved value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form interface.
   *
   * @return array
   *   The form.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  abstract protected function dynamicMappingsForm(
    string $component_id,
    $default_value,
    FormStateInterface $form_state
  ): array;

  /**
   * Adds the component selector to the form.
   *
   * @param array $form
   *   The form array to modify.
   * @param string $input_name
   *   The input name for the checkbox that hides/shows the selector.
   * @param array $stored_values
   *   Previously stored values for this form. Used to populate default values.
   * @param string $tag_name
   *   Name of the tag for SDC Tags.
   * @param string $form_fingerprint
   *   An identifier for this form. Used for the AJAX request to know what form
   *   to update when there are multiple on the page.
   */
  protected function getComponentSelector(
    array &$form,
    string $input_name,
    array $stored_values,
    string $tag_name,
    string $form_fingerprint,
  ): void {
    $machine_name = $stored_values['component']['machine_name'] ?? NULL;
    $form += [
      '#type' => 'details',
      '#title' => t('Single directory components options'),
      '#open' => TRUE,
      '#tree' => TRUE,
      'enabled' => [
        '#type' => 'checkbox',
        '#title' => t('Render using a component'),
        '#default_value' => $stored_values['enabled'] ?? FALSE,
      ],
      'component' => [
        '#title' => t('Component'),
        '#type' => 'cl_component_selector',
        '#default_value' => ['machine_name' => $machine_name],
        '#states' => [
          'visible' => [$input_name => ['checked' => TRUE]],
          'required' => [$input_name => ['checked' => TRUE]],
        ],
        '#filters' => sdc_tags_get_tag_filters($tag_name),
        '#ajax' => [
          'callback' => [static::class, 'componentSelectedAjax'],
          'wrapper' => 'mappings-container--' . $form_fingerprint,
        ],
      ],
      'mappings' => [
        '#type' => 'container',
        '#states' => [
          'visible' => [$input_name => ['checked' => TRUE]],
        ],
        '#prefix' => '<div id="mappings-container--' . $form_fingerprint . '">',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * Get the currently selected component for a given form element.
   *
   * There is no way to identify a form element in a form array other than the
   * dreaded parents array.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string|null $machine_name
   *   The previously selected value. Used when the form element has not changed.
   * @param array $element_parents
   *   The parents to the form element under inspection.
   *
   * @return string|null
   *   The selected component. NULL if none.
   */
  protected function getCurrentlySelectedComponent(FormStateInterface $form_state, ?string $machine_name, array $element_parents): ?string {
    // Get the currently selected component. We can get it from the stored value,
    // from the form values, and in some edge cases from the raw user input.
    $trigger = $form_state->getTriggeringElement();
    if (!$trigger) {
      return $machine_name;
    }
    // We might have several selectors in the same page. We need to ensure that
    // the form element we are currently processing is the one that triggered the
    // AJAX request. Otherwise, we'll validate elements and execute stuff based on
    // someone else's selection. We do this using the parents.
    // Pop the last item, since that will be the triggering button.
    $trigger_parents = array_slice($trigger['#parents'], 0, -1);
    if (!empty(array_diff($trigger_parents, $element_parents))) {
      // If parents are not the same, then the trigger was for some other element.
      return NULL;
    }

    $value_component = $form_state->getValue($trigger['#parents'] ?? NULL);
    $value_component = is_string($value_component) ? $value_component : NULL;
    $user_input = $form_state->getUserInput();
    $input_value_component = is_array($trigger['#parents'] ?? NULL)
      ? NestedArray::getValue($user_input, $trigger['#parents'])
      : NULL;
    return $input_value_component ?? $value_component ?? $machine_name ?? NULL;
  }

  /**
   * Adds the form element to provide the fixed values.
   *
   * It leverages the schema auto-generated forms.
   *
   * @param array $form
   *   The form array to modify.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $selected_component
   *   The component to use for the fixed values (aka static mappings).
   * @param array $current_input
   *   The previously saved input. Used for default values.
   *
   * @return array
   *   The static form section.
   */
  protected function staticMappingsForm(array $form, FormStateInterface $form_state, string $selected_component, array $current_input): array {
    try {
      $static_mappings_form = $this->componentToForm->buildForm(
        $selected_component,
        $current_input,
        $form,
        $form_state,
      );
    }
    catch (ComponentNotFoundException $e) {
      $static_mappings_form = ['#markup' => t('<em>Unable to find component.</em>')];
    }
    return array_merge(
      $static_mappings_form,
      [
        '#type' => 'details',
        '#title' => t('Fixed Values'),
        '#description' => t('Add static mappings to all other props & slots in the component that are not populated by the field value.<br /><strong>IMPORTANT:</strong> required values need to be populated, even if they are mapped to the field value. This mapping will be used in case the field value is empty.'),
        '#description_display' => 'before',
      ],
    );
  }

  /**
   * AJAX callback for the component selector.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public static function componentSelectedAjax(array $form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    if (empty($trigger)) {
      return [];
    }
    // #array_parents is for getting the form element from the big form.
    $array_parents = array_slice($trigger['#array_parents'] ?? [], 0, -3);
    $element = NestedArray::getValue($form, $array_parents);
    // #parents is for getting the submitted value that triggered the AJAX call.
    $component_id = $form_state->getValue($trigger['#parents'] ?? []);
    if (empty($component_id) || !is_string($component_id)) {
      return [];
    }

    return $element['mappings'] ?? [];
  }

  /**
   * Helper function to add description texts.
   *
   * @param array &$form
   *   The form array to alter to add description texts.
   * @param \Drupal\Component\Render\MarkupInterface[] $description_texts
   *   The description texts. The keys are the path to the form element, the
   *   values are the description text.
   */
  protected function setDescriptionTexts(array &$form, array $description_texts): void {
    // Set description text. Set description in nested properties using dot
    // notation.
    foreach ($description_texts as $key => $description_text) {
      $parts = explode('.', $key);
      if (!NestedArray::keyExists($form, $parts)) {
        continue;
      }
      NestedArray::setValue(
        $form,
        [...$parts, '#description'],
        $description_text
      );
    }
  }

}
