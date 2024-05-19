<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

/**
 * Defines imagemagick Convert operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagemagick_convert",
 *   toolkit = "imagemagick",
 *   operation = "convert",
 *   label = @Translation("Convert"),
 *   description = @Translation("Instructs the toolkit to save the image with a specified format.")
 * )
 */
class Convert extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'extension' => [
        'description' => 'The new extension of the converted image',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments): array {
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException("Invalid extension ({$arguments['extension']}) specified for the image 'convert' operation");
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    $this->getToolkit()->arguments()->setDestinationFormatFromExtension($arguments['extension']);
    return TRUE;
  }

}
