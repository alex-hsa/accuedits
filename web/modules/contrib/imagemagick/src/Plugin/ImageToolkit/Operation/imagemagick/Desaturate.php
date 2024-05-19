<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

/**
 * Defines imagemagick Desaturate operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagemagick_desaturate",
 *   toolkit = "imagemagick",
 *   operation = "desaturate",
 *   label = @Translation("Desaturate"),
 *   description = @Translation("Converts an image to grayscale.")
 * )
 */
class Desaturate extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    // This operation does not use any parameters.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    $this->addArguments(['-colorspace', 'GRAY']);
    return TRUE;
  }

}
