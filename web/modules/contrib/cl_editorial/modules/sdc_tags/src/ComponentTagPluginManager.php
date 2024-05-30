<?php

namespace Drupal\sdc_tags;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines a plugin manager to deal with component_tags.
 *
 * Modules can define component_tags in a MODULE_NAME.component_tags.yml file contained
 * in the module's base directory. Each component_tag has the following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     description: STRING
 * @endcode
 *
 * @see \Drupal\sdc_tags\ComponentTagDefault
 * @see \Drupal\sdc_tags\ComponentTagInterface
 * @see plugin_api
 */
class ComponentTagPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The component_tag id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
    // The component_tag label.
    'label' => '',
    // The component_tag description.
    'description' => '',
    // Default plugin class.
    'class' => 'Drupal\sdc_tags\ComponentTagDefault',
  ];

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  private ThemeHandlerInterface $themeHandler;

  /**
   * Constructs ComponentTagPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->alterInfo('component_tag_info');
    $this->setCacheBackend($cache_backend, 'component_tag_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $directories = array_merge(
        $this->moduleHandler->getModuleDirectories(),
        $this->themeHandler->getThemeDirectories()
      );
      $this->discovery = new YamlDiscovery(
        'component_tags',
        $directories
      );
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery->addTranslatableProperty('description', 'description_context');
    }
    return $this->discovery;
  }

}
