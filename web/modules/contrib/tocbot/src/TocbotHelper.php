<?php

namespace Drupal\tocbot;

/**
 * Providers helper functions for tocbot.
 */
class TocbotHelper {

  /**
   * The settings options.
   *
   * @var array
   */
  protected static $settingsOptions = [
    'extra_body_class' => 'extraBodyClass',
    'min_activate' => 'minActivate',
    'create_auto_ids' => 'createAutoIds',
    'toc_selector' => 'tocSelector',
    'content_selector' => 'contentSelector',
    'heading_selector' => 'headingSelector',
    'ignore_selector' => 'ignoreSelector',
    'link_class' => 'linkClass',
    'extra_link_classes' => 'extraLinkClasses',
    'active_link_class' => 'activeLinkClass',
    'list_class' => 'listClass',
    'extra_list_classes' => 'extraListClasses',
    'is_collapsed_class' => 'isCollapsedClass',
    'collapsible_class' => 'collapsibleClass',
    'list_item_class' => 'listItemClass',
    'collapse_depth' => 'collapseDepth',
    'ordered_list' => 'orderedList',
    'scroll_smooth' => 'scrollSmooth',
    'scroll_smooth_duration' => 'scrollSmoothDuration',
    'throttle_timeout' => 'throttleTimeout',
    'position_fixed_selector' => 'positionFixedSelector',
    'position_fixed_class' => 'positionFixedClass',
    'fixed_sidebar_offset' => 'fixedSidebarOffset',
  ];

  /**
   * Find the proper library to use.
   *
   * If the library files exist locally, use internal library, otherwise
   * use the CDN library.
   *
   * @return string
   *   The library to use.
   */
  public static function getLibrary(): string {
    if (
      file_exists(DRUPAL_ROOT . '/libraries/tocbot/dist/tocbot.min.js')
      && file_exists(DRUPAL_ROOT . '/libraries/tocbot/dist/tocbot.css')
    ) {
      return 'tocbot/internal.tocbot';
    }
    return 'tocbot/external.tocbot';
  }

  /**
   * Get the settings options for tocbox.
   *
   * @return string[]
   *   The array with settings options.
   */
  public static function getSettingsOptions(): array {
    return static::$settingsOptions;
  }

}
