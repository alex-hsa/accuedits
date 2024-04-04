<?php

/**
 * @file
 * Contains \Drupal\responsive_tables_filter\Tests\TestBase.
 */

namespace Drupal\Tests\responsive_tables_filter\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for all responsive_tables_filter tests.
 */
abstract class TestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'responsive_tables_filter', 'node'];

  /**
   * {@inheritdoc}
   */
  public $defaultTheme = 'stark';

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $mode = 'stack';

  /**
   * A set up for all tests.
   */
  protected function setUp():void {
    parent::setUp();

    // Create a page content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page'
    ]);

    // Create a text format and enable the responsive_tables_filter filter.
    $format = FilterFormat::create([
      'format' => 'custom_format',
      'name' => 'Custom format',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<a href> <p> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <br> <h3 id> <table class additional> <th> <tr> <td> <thead> <tbody> <tfoot>',
          ],
        ],
        'filter_responsive_tables_filter' => [
          'status' => 1,
          'settings' => [
            'tablesaw_type' => $this->mode,
          ],
        ],
      ],
    ]);
    $format->save();

    // Create a user with required permissions.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'use text format custom_format',
    ]);
    $this->drupalLogin($this->webUser);
  }

}
