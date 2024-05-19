<?php

namespace Drupal\smartmenus;

use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Class SmartmenusUtil
 * @package Drupal\smartmenus
 */
class SmartmenusUtil {

  /**
   * @var TranslationManager
   */
  protected $translation;

  /**
   * SmartmenusUtil constructor.
   * @param TranslationManager $translation
   */
  public function __construct(TranslationManager $translation) {
      $this->translation = $translation;
  }

  /**
   * @return array|string
   * Returns a list of themes supported by the Smartmenus plugin.
   */
  public function getAvailableMenuThemesList(): array {
    return [
      '' => $this->translation->translate('None'),
      'sm-blue' => $this->translation->translate('Blue'),
      'sm-clean' => $this->translation->translate('Clean'),
      'sm-mint' => $this->translation->translate('Mint'),
      'sm-simple' => $this->translation->translate('Simple'),
    ];
  }

  /**
   * @return string
   */
  public function getConfigFormSettingsName(): string {
    return 'smartmenus.smartmenussettings';
  }
}
