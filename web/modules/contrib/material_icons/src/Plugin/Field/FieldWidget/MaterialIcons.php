<?php

namespace Drupal\material_icons\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\material_icons\Traits\MaterialIconsSettings;

/**
 * Plugin implementation of the 'Material Icons' widget.
 *
 * @FieldWidget(
 *   id = "material_icons",
 *   label = @Translation("Material Icons"),
 *   field_types = {
 *     "material_icons"
 *   }
 * )
 */
class MaterialIcons extends WidgetBase implements ContainerFactoryPluginInterface {

  use MaterialIconsSettings;

  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
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
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allow_style' => TRUE,
      'default_style' => '',
      'allow_classes' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['allow_style'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Style Selection'),
      '#default_value' => $this->getSetting('allow_style'),
    ];

    $element['default_style'] = [
      '#type' => 'select',
      '#options' => $this->getStyleOptions(),
      '#title' => $this->t('Default Style'),
      '#default_value' => $this->getSetting('default_style'),
    ];

    $element['allow_classes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Additional Classes'),
      '#default_value' => $this->getSetting('allow_classes'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Allow Styles: @allow_style', ['@allow_style' => $this->getSetting('allow_style') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Defailt Style: @default_style', ['@default_style' => $this->getSetting('default_style')]);
    $summary[] = $this->t('Allow Additional Classes: @allow_classes', ['@allow_classes' => $this->getSetting('allow_classes') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Gets font family from $form_state, if available.
    $font_family = $this->getFormStateFontFamily($form_state)
      ??
      ($items[$delta]->get('family')->getValue()
        ??
        $this->getSetting('default_style'));

    // Unique Id layer wrapper.
    $form_id = $form_state->getBuildInfo()['form_id'];
    $field_name = $items->getName();
    $field_wrapper_id = "{$form_id}__{$field_name}__{$delta}";

    // Icon autocomplete.
    $element['icon'] = [
      '#type' => 'textfield',
      '#title' => $cardinality == 1 ? $this->fieldDefinition->getLabel() : $this->t('Icon Name'),
      '#default_value' => $items[$delta]->get('icon')->getValue(),
      '#required' => $element['#required'],
      '#description' => $this->t('Name of the Material Design Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
        '@iconsLink' => Link::fromTextAndUrl(
          $this->t('the icon list'),
          Url::fromUri('https://material.io/resources/icons', ['attributes' => ['target' => '_blank']])
        )->toString(),
      ]),
      '#autocomplete_route_name' => 'material_icons.autocomplete',
      '#autocomplete_route_parameters' => [
        'font_family' => $font_family,
      ],
      '#prefix' => "<div id=\"{$field_wrapper_id}\">",
      '#suffix' => '</div>',
    ];

    // Family dropdown.
    $element['family'] = [
      '#title' => $this->t('Icon Style'),
      '#type' => 'select',
      '#default_value' => $font_family,
      '#options' => $this->getStyleOptions(),
      '#disabled' => !$this->getSetting('allow_style'),
      '#ajax' => [
        'callback' => [$this, 'handleIconStyleUpdated'],
        'event' => 'change',
        'wrapper' => $field_wrapper_id,
      ],
    ];

    // Field classes, if activated.
    if ($this->getSetting('allow_classes')) {
      $element['classes'] = [
        '#title' => $this->t('Additional Classes'),
        '#type' => 'textfield',
        '#default_value' => $items[$delta]->get('classes')->getValue(),
        '#description' => $this->t('For example, veritical alignment classes: <em>align-text-top</em>'),
      ];
    }

    return $element;
  }

  /**
   * Updated the value of the Icon Style field.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   * @return array
   */
  public function handleIconStyleUpdated(array &$form, FormStateInterface $form_state) {
    return $this->getFormIconField($form, $form_state);
  }

  /**
   * Gets the underlying field name of the triggering element.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   * @return array|Null
   */
  private function getFormIconField(array $form, FormStateInterface $form_state):array|Null {
    $parents = $this->getFormStateStructure($form_state);
    return (!is_null($parents)) ? $form[$parents[3]][$parents[2]][$parents[1]]['icon'] : NULL;
  }

  /**
   * Gets the selected value of the font family.
   * @param FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   * @return string|Null
   */
  private function getFormStateFontFamily(FormStateInterface $form_state):string|Null {
    $parents = $this->getFormStateStructure($form_state);
    return (!is_null($parents)) ? $form_state->getValue($parents[3])[$parents[1]]['family'] : NULL;
  }

  /**
   * Gets the selected value of the font family.
   * @param FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   * @return array|Null
   *   This is the array structure being delivered:
   *     0 => 'family'
   *     1 => [delta integer]
   *     2 => 'widget'
   *     3 => [original field name]
   */
  private function getFormStateStructure(FormStateInterface $form_state):array|Null {
    $triggering_element = $form_state->getTriggeringElement();

    if (empty($triggering_element)) {
      return NULL;
    }

    $parents = array_reverse($triggering_element['#array_parents']);
    return (array_key_exists(1, $parents) && array_key_exists(3, $parents)) ? $parents : NULL;
  }

  /**
   * Helper to produce a list of available icon styles.
   *
   * @return array
   *   The available options.
   */
  protected function getStyleOptions() {
    $available_families = $this->configFactory->get('material_icons.settings')->get('families');
    return array_intersect_key($this->getFontFamilies(), array_flip($available_families));
  }

}
