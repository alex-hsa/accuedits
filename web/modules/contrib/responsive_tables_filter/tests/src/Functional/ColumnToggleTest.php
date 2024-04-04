<?php

namespace Drupal\Tests\responsive_tables_filter\Functional;

/**
 * Tests the responsive_tables_filter filter.
 *
 * @group responsive_tables_filter
 */
class ColumnToggleTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $mode = 'columntoggle';

  /**
   * Input & output for stack mode.
   *
   * @var data
   */
  private static $data = [
    '<table class="no-tablesaw"><thead></thead></table>' => '<table class="no-tablesaw"><thead></thead></table>',
    '<table><thead></thead></table>' => '<table class="tablesaw tablesaw-columntoggle" data-tablesaw-mode="columntoggle" data-tablesaw-minimap><thead></thead></table>',
    '<table class="test"><thead></thead></table>' => '<table class="test tablesaw tablesaw-columntoggle" data-tablesaw-mode="columntoggle" data-tablesaw-minimap><thead></thead></table>',
    '<table additional="test"><thead><tr><th data-tablesaw-priority="persist">Header One<th>Header 2<tbody><tr><td>Easily add tables with the WYSIWYG toolbar<td>Encoded characters test öô & , ?<tr><td>Tables respond to display on smaller screens<td>Fully accessible to screen readers</table>' => '<table additional="test" class="tablesaw tablesaw-columntoggle" data-tablesaw-mode="columntoggle" data-tablesaw-minimap><thead><tr><th role="columnheader" data-tablesaw-sortable-col data-tablesaw-priority="1">Header One</th><th role="columnheader" data-tablesaw-sortable-col data-tablesaw-priority="2">Header 2</th></tr></thead><tbody><tr><td>Easily add tables with the WYSIWYG toolbar</td><td>Encoded characters test öô &amp; , ?</td></tr><tr><td>Tables respond to display on smaller screens</td><td>Fully accessible to screen readers</td></tr></tbody></table>',
  ];

  /**
   * Tests the responsive_tables_filter Column Toggle mode.
   */
  public function testColumnToggle() {
    $page = $this->getSession()->getPage();
    foreach (self::$data as $input => $expected) {
      $settings = [];
      $settings['type'] = 'page';
      $settings['title'] = 'Test Tablesaw Column Toggle mode';
      $settings['body'] = [
        'value' => $input,
        'format' => 'custom_format',
      ];
      $node = $this->drupalCreateNode($settings);
      $this->drupalGet('node/' . $node->id());
      $table = $page->find('css', 'table');
      $this->assertEquals($expected, $table->getOuterHtml());
    }
  }

}
