<?php

namespace Drupal\Tests\sdc_display\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that view modes render through a component.
 *
 * @group sdc_display
 *
 * @internal
 */
final class ComponentViewModeRenderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'field_ui', 'sdc_display', 'sdc_test'];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->adminUser = $this->drupalCreateUser(['access content', 'administer node display']);
    $display = EntityViewDisplay::load('node.article.default');
    $display->setThirdPartySetting('sdc_display', 'enabled', '1');
    $display->setThirdPartySetting('sdc_display', 'component', [
      'machine_name' => 'sdc_test:my-banner',
    ]);
    $display->setThirdPartySetting('sdc_display', 'mappings', [
      'static' => [
        'props' => [
          'heading' => '',
          'ctaText' => 'Click me',
          'ctaHref' => 'https://www.example.org',
          'ctaTarget' => '_blank',
          'image' => '',
        ],
        'slots' => [
          'banner_body' => [
            'value' => 'Default body',
            'format' => 'plain_text',
          ],
        ],
      ],
      'dynamic' => [
        'props' => [
          'heading' => 'title',
          'ctaText' => '',
          'ctaHref' => '',
          'ctaTarget' => '',
          'image' => '',
        ],
        'slots' => [
          'banner_body' => [
            'body' => 'body',
            'uid' => NULL,
            'title' => NULL,
            'created' => NULL,
          ],
        ],
      ],
    ]);
    $display->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests view mode render.
   */
  public function testViewModeRender(): void {
    $session = $this->assertSession();

    // 1. Test with values.
    $node1 = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'type' => 'article',
      'body' => $this->randomString(),
    ]);
    $this->drupalGet('/node/' . $node1->id());
    $session->elementTextEquals('css', '[data-component-id="sdc_test:my-banner"] h3', $node1->getTitle());
    $session->elementTextEquals('css', '[data-component-id="sdc_test:my-banner"] .component--my-banner--body', $node1->body->value);
    $cta = $session->elementExists('css', '[data-component-id="sdc_test:my-cta"]');
    $this->assertSame('_blank', $cta->getAttribute('target'));
    $this->assertSame('https://www.example.org', $cta->getAttribute('href'));
    $this->assertSame('Click me', $cta->getText());

    // 2. Test without values.
    $node2 = $this->drupalCreateNode([
      'type' => 'article',
      'body' => ['value' => NULL],
    ]);
    $this->drupalGet('/node/' . $node2->id());
    $session->elementTextEquals('css', '[data-component-id="sdc_test:my-banner"] .component--my-banner--body', 'Default body');
  }

}
