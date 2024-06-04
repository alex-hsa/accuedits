<?php

namespace Drupal\Tests\bg_image_formatter\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * @coversDefaultClass \Drupal\bg_image_formatter\Plugin\Field\FieldFormatter\BgImageFormatter
 *
 * @group bg_image_formatter
 */
class BgImageFormatterTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bg_image_formatter',
    'field',
    'file',
    'filter',
    'image',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * @covers ::mergeDefaults
   */
  public function testSettings(): void {
    $this->installConfig('node');
    $node_type = $this->createContentType();
    /** @var \Drupal\field\FieldConfigInterface $image_field */
    $image_field = $this->createImageField('field_bg_image', $node_type->id());

    /** @var \Drupal\bg_image_formatter\Plugin\Field\FieldFormatter\BgImageFormatter $plugin */
    $plugin = $this->container->get('plugin.manager.field.formatter')
      ->createInstance('bg_image_formatter', [
        'field_definition' => $image_field,
        'settings' => [
          'image_style' => 'test',
          'css_settings' => [
            'bg_image_selector' => 'custom-element',
          ],
        ],
        'label' => $image_field->label(),
        'view_mode' => EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
        'third_party_settings' => [],
      ]);

    $settings = $plugin->getSettings();
    $default_settings = $plugin::defaultSettings();
    // The overridden settings should be preserved.
    $this->assertSame('test', $settings['image_style']);
    $this->assertSame('custom-element', $settings['css_settings']['bg_image_selector']);
    // Other, unspecified settings should be defaulted.
    $this->assertSame($settings['css_settings']['bg_image_color'], $default_settings['css_settings']['bg_image_color']);
  }

}
