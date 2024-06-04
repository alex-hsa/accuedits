<?php

namespace Drupal\paragraphs_responsive_background_image_formatter;

/**
 * Formatter settings service.
 */
class SettingsService {

  /**
   * The settings array generated from hooks.
   *
   * @var array
   */
  public static array $formatterSettings = [];

  /**
   * Get the formatter settings.
   */
  public function getFormatterSettings(): array {
    return self::$formatterSettings;
  }

  /**
   * Set the formatter settings.
   */
  public function setFormatterSettings($settings = []) {
    self::$formatterSettings = $settings;
    return $settings;
  }

}
