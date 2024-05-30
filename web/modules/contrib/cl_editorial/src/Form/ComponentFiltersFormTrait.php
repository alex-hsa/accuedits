<?php

namespace Drupal\cl_editorial\Form;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\sdc\Plugin\Component;
use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait to create a for to configure filters for the component selector.
 */
trait ComponentFiltersFormTrait {

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\cl_editorial\NoThemeComponentManager $component_manager
   *   The component manager.
   * @param array $settings
   *   Settings array.
   * @param array $parents
   *   The array parents where this form is embedded.
   * @param \Drupal\Component\Render\MarkupInterface $message
   *   The info message.
   */
  public static function buildSettingsForm(array &$form, FormStateInterface $form_state, NoThemeComponentManager $component_manager, array $settings, array $parents, MarkupInterface $message): void {
    // Check the user input for the existing values.
    $values = $form_state->getValues();
    $raw_input = $form_state->getUserInput();
    $clean = static fn (array $item) => array_values(array_filter($item));
    $statuses = NestedArray::getValue($values, [...$parents, 'filters', 'statuses'])
      ?? NestedArray::getValue($raw_input, [...$parents, 'filters', 'statuses'])
      ?? $settings['statuses']
      ?? [];
    $statuses = $clean($statuses);
    $components = $component_manager->getFilteredComponents([], [], $statuses);
    $options = array_reduce(
      $components,
      static fn (array $carry, Component $component) => [
        ...$carry,
        $component->getPluginId() => sprintf('<span title="%s">%s<span>', $component->getPluginId(), $component->metadata->name),
      ],
      []
    );
    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => t('Filters'),
      '#tree' => TRUE,
    ];
    $form['filters']['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $message,
    ];
    $form['filters']['statuses'] = [
      '#type' => 'checkboxes',
      '#title' => t('Component Status'),
      '#description' => t('Only components of these status will show in the component list.'),
      '#options' => [
        ExtensionLifecycle::STABLE => t('Stable'),
        ExtensionLifecycle::EXPERIMENTAL => t('Experimental'),
        ExtensionLifecycle::DEPRECATED => t('Deprecated'),
        ExtensionLifecycle::OBSOLETE => t('Obsolete'),
      ],
      '#default_value' => $settings['statuses'],
      '#ajax' => [
        'wrapper' => 'refinement-wrapper',
        'effect' => 'fade',
        'callback' => [static::class, 'onChangeAjaxCallback'],
      ],
    ];
    $form['filters']['refine'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Refine selection'),
      '#prefix' => '<div id="refinement-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['filters']['refine']['forbidden'] = [
      '#type' => 'checkboxes',
      '#title' => t('Forbidden Components'),
      '#description' => t('Settings will apply to all Single Directory Components, except the ones listed here. This is used to limit the components available for a given content type.'),
      '#options' => $options,
      '#default_value' => $settings['forbidden'],
      '#attributes' => ['class' => ['long-checkboxes-list']],
    ];
    $form['filters']['refine']['allowed'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed Components'),
      '#description' => t('Settings will only apply the Single Directory Components selected here. <strong>NOTE:</strong> "forbidden" components will not apply even when allowed here.'),
      '#options' => $options,
      '#default_value' => $settings['allowed'],
      '#attributes' => ['class' => ['long-checkboxes-list']],
    ];
    $form['filters']['#attached']['library'][] = 'cl_editorial/filter-settings';
  }

  /**
   * Validator callback.
   */
  public static function validates(array &$form, FormStateInterface $form_state): void {
    $forbidden = array_keys(array_filter($form_state->getValue(['filters', 'refine', 'forbidden'])));
    $allowed = array_keys(array_filter($form_state->getValue(['filters', 'refine', 'allowed'])));
    $both_lists = array_intersect($forbidden, $allowed);
    if (!empty($both_lists)) {
      $message = t('The following components are added to both forbidden and allow lists. Please review: %components', ['%components' => implode(', ', $both_lists)]);
      $form_state->setErrorByName('filters][refine][forbidden', $message);
      $form_state->setErrorByName('filters][refine][allowed', $message);
    }
  }

  /**
   * Ajax callback to regenerate the list of components.
   */
  public static function onChangeAjaxCallback(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Pop the checkbox value and the "statuses" from the parents.
    // This should leave us with the parents to the "filters".
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);
    return NestedArray::getValue($element, [...$parents, 'refine']);
  }

}
