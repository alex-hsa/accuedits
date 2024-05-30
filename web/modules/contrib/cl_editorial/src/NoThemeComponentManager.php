<?php

namespace Drupal\cl_editorial;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\sdc\Component\ComponentMetadata;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\ExtensionType;
use Drupal\sdc\Plugin\Component;

/**
 * Decorates the component plugin manager to add more features.
 */
class NoThemeComponentManager {

  /**
   * Constructs a NoThemeComponentManager.
   *
   * @param \Drupal\sdc\ComponentPluginManager $pluginManager
   *   The component plugin manager.
   */
  public function __construct(protected readonly ComponentPluginManager $pluginManager) {
  }

  /**
   * Returns the components matching the filters.
   *
   * @param array $allowed
   *   The allowed components.
   * @param array $forbidden
   *   The forbidden components.
   * @param array $statuses
   *   The allowed component statuses.
   *
   * @return \Drupal\sdc\Plugin\Component[]
   *   The list of allowed values.
   *
   * @internal
   */
  public function getFilteredComponents(
    array $allowed = [],
    array $forbidden = [],
    array $statuses = [
      ExtensionLifecycle::STABLE,
      ExtensionLifecycle::EXPERIMENTAL,
    ]
  ): array {
    $plugin_ids = array_keys($this->getDefinitionsWithoutReplacements());
    $components = array_values(array_filter(array_map(
      [$this, 'createInstanceAndCatch'],
      $plugin_ids
    )));
    $filter_components = static function (Component $component) use ($statuses, $allowed, $forbidden) {
      $metadata = $component->metadata;
      if (!in_array($metadata->status, $statuses, TRUE)) {
        return FALSE;
      }
      // If there are allowed components and this is not one of them, continue.
      if (!empty($allowed) && !in_array($component->getPluginId(), $allowed, TRUE)) {
        return FALSE;
      }
      // If this is a forbidden component, continue.
      if (in_array($component->getPluginId(), $forbidden, TRUE)) {
        return FALSE;
      }
      return TRUE;
    };
    $filtered_components = array_reduce(
      array_filter($components, $filter_components),
      static fn(array $carry, Component $component) => [
        ...$carry,
        $component->getPluginId() => $component,
      ],
      []
    );
    ksort($filtered_components);
    return $filtered_components;
  }

  /**
   * Gets the plugin definitions after resolving plugin overrides.
   *
   * @return array[]
   *   The definition arrays.
   *
   * @internal
   */
  protected function getDefinitionsWithoutReplacements(): array {
    return array_filter(
      $this->getDefinitions(),
      static fn (array $definition) => empty($definition['replaces']),
    );
  }

  /**
   * Proxies the createInstance method.
   */
  public function createInstanceAndCatch($plugin_id, array $configuration = []) {
    try {
      return $this->pluginManager->createInstance($plugin_id, $configuration);
    }
    catch (ComponentNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * Proxies the getDefinitions method.
   */
  public function getDefinitions(): ?array {
    return $this->pluginManager->getDefinitions();
  }

  /**
   * Proxies the find method.
   *
   * @throws \Drupal\sdc\Exception\ComponentNotFoundException
   */
  public function find(string $id): Component {
    return $this->pluginManager->find($id);
  }

}
