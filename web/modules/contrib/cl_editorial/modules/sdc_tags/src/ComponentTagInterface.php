<?php

namespace Drupal\sdc_tags;

/**
 * Interface for component_tag plugins.
 */
interface ComponentTagInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
