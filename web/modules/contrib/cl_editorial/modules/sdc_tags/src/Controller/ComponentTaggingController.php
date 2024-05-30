<?php

namespace Drupal\sdc_tags\Controller;

use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;
use Drupal\sdc_tags\ComponentTagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Configure Component Libraries: Editorial settings for this site.
 */
class ComponentTaggingController extends ControllerBase {

  /**
   * The configuration name.
   *
   * @var string
   */
  private string $configName = 'sdc_tags.settings';

  /**
   * The component tag manager.
   *
   * @var \Drupal\sdc_tags\ComponentTagPluginManager
   */
  private ComponentTagPluginManager $componentTagManager;

  /**
   * The component manager.
   *
   * @var \Drupal\sdc\ComponentPluginManager
   */
  private ComponentPluginManager $componentManager;

  /**
   * The component manager that disregards active theme.
   *
   * @var \Drupal\cl_editorial\NoThemeComponentManager
   */
  private NoThemeComponentManager $noThemeComponentManager;

  /**
   * Creates a new form object.
   *
   * @param \Drupal\sdc_tags\ComponentTagPluginManager $component_tag_manager
   *   The component tag manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ComponentTagPluginManager $component_tag_manager,
    ComponentPluginManager $component_manager,
    NoThemeComponentManager $no_theme_component_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->componentTagManager = $component_tag_manager;
    $this->componentManager = $component_manager;
    $this->noThemeComponentManager = $no_theme_component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.sdc_tags.component_tag'),
      $container->get('plugin.manager.sdc'),
      $container->get(NoThemeComponentManager::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke() {
    $build = [];
    // Get a list of all the components.
    $component_definitions = $this->componentManager->getDefinitions();
    // Get a list of all the tags.
    $tags = $this->componentTagManager->getDefinitions();
    // Get the default values.
    $config = $this->configFactory->get($this->configName)
      ->get('component_tags');
    $default_values = array_reduce(
      $config,
      function(array $carry, array $item) {
        $tag_id = $item['tag_id'];
        unset($item['tag_id']);
        $components = $this->noThemeComponentManager
          ->getFilteredComponents(...$item);
        return array_merge(
          $carry,
          [$tag_id => array_keys($components)],
        );
      },
      [],
    );

    $build['instructions'] = [
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Select the components that apply for each tag. Tags are used by modules so components can opt-in to certain features. This typically includes appearing on the component selector in a site building tool that integrates components.'),
      ],
      'legend' => [
        '#type' => 'details',
        '#title' => $this->t('Component Information'),
        '#description' => $this->t('Tags are registered in modules and themes in the *.component_tags.yml. Tags consist of an ID, a label, and a description.'),
        'table' => [
          '#theme' => 'table',
          '#header' => [
            $this->t('ID'),
            $this->t('Name'),
            $this->t('Description'),
            NULL,
          ],
          '#rows' => array_map(
            function(array $definition): array {
              $rows = [
                $definition['id'],
                $definition['name'] ?? '',
                $definition['description'] ?? '',
              ];
              try {
                $url = Url::fromRoute(
                  'cl_devel.component_details',
                  ['component_id' => $definition['id']],
                );
                // Render the URL early to catch the not found exception.
                $url->toString(TRUE)->getGeneratedUrl();
                $rows[] = [
                  'data' => [
                    '#type' => 'link',
                    '#url' => $url,
                    '#title' => $this->t('More Information'),
                    '#options' => [
                      'attributes' => [
                        'class' => ['button', 'button--small'],
                      ],
                    ],
                  ],
                ];
              }
              catch (RouteNotFoundException $e) {
                // This means that cl_devel is not installed. No big deal.
              }
              return $rows;
            },
            $component_definitions,
          ),
        ],
      ],
    ];
    $build['component_tags'] = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Available component tags'),
      ],
      'components' => array_reduce(
        $tags,
        function(array $carry, array $tag_definition) use ($config, $component_definitions, $default_values) {
          $has_tagging_config = !empty($config[$tag_definition['id']]);
          $summary = $this->summaryFromConfig($config[$tag_definition['id']] ?? NULL);
          $action = [
            '#type' => 'container',
            '#attributes' => ['class' => ['tag-rules-actions']],
            'button' => [
              '#type' => 'link',
              '#url' => Url::fromRoute(
                'sdc_tags.auto_tagging',
                ['tag' => $tag_definition['id']],
              ),
              '#title' => !$has_tagging_config
                ? $this->t('Create Tagging Rules')
                : $this->t('Update Tagging Rules'),
              '#options' => [
                'attributes' => [
                  'class' => [
                    'button',
                    'button--small',
                    'button--action',
                    ...($has_tagging_config ? [] : ['button--primary']),
                  ],
                ],
              ],
            ],
            'summary' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $summary,
            ],
          ];
          $selected_definition_ids = array_column(array_filter(
            $component_definitions,
            static fn(array $component_definition) => in_array($component_definition['id'], $default_values[$tag_definition['id']] ?? [], TRUE),
          ), 'id');
          $plugin_manager = \Drupal::service('plugin.manager.sdc');
          $selected_components = array_map(
            [$plugin_manager, 'createInstance'],
            $selected_definition_ids,
          );
          if (empty($selected_components)) {
            $component_list = [
              '#type' => 'html_tag',
              '#tag' => 'em',
              '#value' => $this->t('No components tagged with this tag yet. Create or update the tagging rules.'),
            ];
          }
          else {
            $component_list = array_reduce(
              $selected_components,
              static function(array $items, Component $component) {
                return [
                  ...$items,
                  $component->getPluginId() => [
                    '#wrapper_attributes' => [
                      'title' => sprintf('%s (%s)', $component->metadata->name, $component->getPluginId()),
                    ],
                    'component' => [
                      '#type' => 'component',
                      '#component' => 'cl_editorial:component-card',
                      '#props' => [
                        'name' => $component->metadata->name,
                        'description' => $component->metadata->description,
                        'group' => $component->metadata->group,
                        'status' => $component->metadata->status,
                        'thumbnailHref' => $component->metadata->getThumbnailPath() ? \Drupal::service('file_url_generator')
                          ->generateAbsoluteString($component->metadata->getThumbnailPath()) : '',
                      ],
                    ],
                  ],
                ];
              },
              []
            );
          }
          $row = [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => $tag_definition['label'],
            '#description' => $tag_definition['description'],
            '#attributes' => ['class' => ['component-tags']],
            'auto-tagging' => $action,
            'list_title' => $tag_definition['description'] ?? NULL ? [
              '#type' => 'html_tag',
              '#tag' => 'h4',
              '#value' => $this->t('Tagged Components'),
            ] : [],
            'components' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['component-cards--wrapper']],
              ...$component_list,
            ],
          ];
          return [...$carry, $tag_definition['id'] => $row];
        },
        []
      ),
    ];
    $build['component_tags']['#tree'] = TRUE;
    $build['component_tags']['#attached']['library'][] = 'sdc_tags/tagging';

    return $build;
  }

  /**
   * Generates a summary of the configuration.
   *
   * @param array|null $config
   *   The configuration.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  private function summaryFromConfig(?array $config = NULL): MarkupInterface {
    if ($config === NULL) {
      return $this->t('<em>There are no tag rules for this tag yet.</em>');
    }
    $statuses = $config['statuses'] ?? [];
    if (empty($statuses)) {
      return $this->t('No components of any status are allowed.');
    }
    $allowed = $config['allowed'] ?? [];
    if (!empty($allowed)) {
      return $this->t('Several components are manually allowed if their status is one of [%statuses].', ['%statuses' => implode(', ', $statuses)]);
    }
    $forbidden = $config['forbidden'] ?? [];
    if (!empty($forbidden)) {
      return $this->t('All components are allowed if their status is one of [%statuses], except for a manual selection of components.', ['%statuses' => implode(', ', $statuses)]);
    }
    return $this->t('All components are allowed if their status is one of [%statuses].', ['%statuses' => implode(', ', $statuses)]);
  }

}
