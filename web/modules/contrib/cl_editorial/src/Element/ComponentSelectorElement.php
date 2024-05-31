<?php

namespace Drupal\cl_editorial\Element;

use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Utility\Error;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\Plugin\Component;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a component selector element.
 *
 * @FormElement("cl_component_selector")
 */
class ComponentSelectorElement extends FormElement implements ContainerFactoryPluginInterface {

  protected const DEFAULT_STATUSES = [
    ExtensionLifecycle::STABLE,
    ExtensionLifecycle::EXPERIMENTAL,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected NoThemeComponentManager $componentManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $component_manager = $container->get(NoThemeComponentManager::class);
    assert($component_manager instanceof NoThemeComponentManager);
    $file_url_generator = $container->get('file_url_generator');
    assert($file_url_generator instanceof FileUrlGeneratorInterface);
    return new static($configuration, $plugin_id, $plugin_definition, $component_manager, $file_url_generator);
  }

  /**
   * @inheritDoc
   */
  public function getInfo() {
    return [
      '#title' => $this->t('Single Directory Components'),
      '#description' => $this->t('Select the suitable SDC component.'),
      '#description_display' => 'before',
      '#filters' => [],
      '#process' => [[$this, 'populateOptions']],
      '#element_validate' => [[$this, 'validateExistingComponent']],
      '#theme_wrappers' => ['cl_component_selector', 'fieldset'],
      '#submit' => [[$this, 'submitForm']],
      '#input' => TRUE,
    ];
  }

  /**
   * Form API process callback.
   */
  public function populateOptions(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form
  ): array {
    $filters = $element['#filters'] ?? [];
    $components = $this->componentManager->getFilteredComponents(
      $filters['allowed'] ?? [],
      $filters['forbidden'] ?? [],
      $filters['statuses'] ?? static::DEFAULT_STATUSES,
    );

    $options = array_reduce(
      $components,
      static fn(array $carry, Component $component) => [
        ...$carry,
        $component->getPluginId() => $component->metadata->name,
      ],
      []
    );
    ksort($options);

    $default_id = $element['#default_value']['machine_name'] ?? NULL;
    $default_component = ($default_id ? $components[$default_id] : NULL) ?? NULL;
    $element += [
      '#attached' => ['library' => ['cl_editorial/selector']],
    ];

    $element['search'] = [
      '#title' => $this->t('Search'),
      '#title_display' => 'hidden',
      '#type' => 'search',
      '#default_value' => $default_component instanceof Component
        ? $default_component->getPluginId()
        : NULL,
      '#placeholder' => $this->t('Search for a component'),
      '#size' => 50,
      '#description' => $this->t('Start typing to search for a component.'),
      '#input' => FALSE,
      '#attributes' => [
        'class' => [
          'search-box',
        ],
      ],
    ];
    $element['show_deprecated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show deprecated components'),
      '#default_value' => FALSE,
      '#attributes' => [
        'class' => [
          'deprecation-checkbox',
        ],
      ],
    ];
    $element['machine_name'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#title' => $this->t('Components'),
      '#title_display' => 'invisible',
      '#default_value' => $default_component ? $default_component->getPluginId() : NULL,
      '#process' => [
        [Radios::class, 'processRadios'],
        [$this, 'processRadios'],
      ],
      '#weight' => 1,
      '#attributes' => [
        'class' => ['component-selector--radios'],
      ],
      '#ajax' => $element['#ajax'] ?? FALSE,
      '#input' => FALSE,
    ];
    $classes = $element['#attributes']['class'] ?? [];
    $classes[] = 'component--selector';
    $element['#attributes']['class'] = $classes;
    unset($element['#default_value'], $element['#ajax'], $element['#options']);
    return $element;
  }

  /**
   * Process the radios.
   */
  public function processRadios(array $element, FormStateInterface $form_state): array {
    $keys = Element::children($element);
    $component_ids = array_filter(
      array_map(
        static fn(array $item) => $item['#return_value'] ?? NULL,
        array_intersect_key($element, array_flip($keys))
      )
    );
    $components = array_filter(array_map(
      [$this->componentManager, 'createInstanceAndCatch'],
      $component_ids
    ));
    foreach ($keys as $key) {
      $element[$key]['#theme_wrappers'] = [
        'form_element__radio__cl_component',
        'form_element__radio',
      ];
      $id = $element[$key]['#return_value'];
      $component = $components[$id];
      assert($component instanceof Component);
      $metadata = $component->metadata;
      $element[$key]['#title_display'] = 'hidden';
      $element[$key]['#human_name'] = $metadata->name;
      $element[$key]['#machine_name'] = $component->getPluginId();
      $element[$key]['#component_description'] = $metadata->description;
      $element[$key]['#component_status'] = $metadata->status;
      $element[$key]['#group'] = $metadata->group;
      $element[$key]['#thumbnail_url'] = $metadata->getThumbnailPath() ? $this->fileUrlGenerator->generateAbsoluteString($metadata->getThumbnailPath()) : NULL;
      $docs = NULL;
      if (class_exists('League\CommonMark\CommonMarkConverter')) {
        $converter = new CommonMarkConverter();
        try {
          $docs = $converter->convert($metadata->documentation)->getContent();
        }
        catch (CommonMarkException $e) {
          Error::logException(\Drupal::logger('cl_editorial'), $e);
        }
      }
      $docs = $docs ?? nl2br(
        $metadata->documentation . PHP_EOL .
        $this->t('<em>NOTE: Install <code>composer require league/commonmark</code> to render MarkDown documentation.</em>')
      );
      $element[$key]['#readme'] = Xss::filterAdmin($docs);
    }
    return $element;
  }

  /**
   * Validator for form element.
   */
  public function validateExistingComponent(array $element, FormStateInterface $form_state): void {
    $parents = $element['#parents'] ?? [];
    $parents[] = 'machine_name';
    $value = $form_state->getValue($parents);
    if (!is_scalar($value)) {
      $value = NULL;
      $form_state->setValue($parents, NULL);
    }
    if (!$value) {
      return;
    }
    if (empty($this->componentManager->getDefinitions()[$value])) {
      $form_state->setError(
        $element['machine_name'],
        $this->t('Invalid component ID: @id', ['@id' => $value])
      );
    }
  }

}
