<?php

namespace Drupal\responsive_tables_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Responsive Tables Filter class. Implements process() method only.
 *
 * @Filter(
 *   id = "filter_responsive_tables_filter",
 *   title = @Translation("Apply responsive behavior to HTML tables."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "tablesaw_type" = "stack",
 *     "tablesaw_persist" = TRUE
 *   }
 * )
 */
class FilterResponsiveTablesFilter extends FilterBase {

  /**
   * Available Tablesaw modes.
   *
   * @var modes
   */
  public static $modes = [
    'stack' => "Stack Mode",
    'columntoggle' => "Column Toggle Mode",
    'swipe' => "Swipe Mode",
  ];

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($filtered = $this->runFilter($text)) {
      $result = new FilterProcessResult($filtered);
      // Attach Tablesaw library assets to this page.
      $result->setAttachments([
        'library' => ['responsive_tables_filter/tablesaw-filter'],
      ]);
    }
    else {
      $result = new FilterProcessResult($text);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['tablesaw_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default mode'),
      '#default_value' => $this->settings['tablesaw_type'] ?? 'stack',
      '#description' => $this->t('This will apply by default to tables in WYSIWYGs, but can be overridden on an individual basis by adding the <code>class</code> "tablesaw-stack", "tablesaw-columntoggle", or "tablesaw-swipe" to the <code>table</code> tag. See documentation: https://github.com/filamentgroup/tablesaw'),
      '#options' => self::$modes,
    ];

    $form['tablesaw_persist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Persistent first column?'),
      '#default_value' => $this->settings['tablesaw_persist'] ?? TRUE,
      '#description' => $this->t('This will apply to all tables in WYSIWYGs.'),
    ];
    return $form;
  }

  /**
   * Business logic for adding classes & attributes to <table> tags.
   */
  public function runFilter($text) {
    // Older versions of libxml always add DOCTYPE, <html>, and <body> tags.
    // See http://www.php.net/manual/en/libxml.constants.php.
    // Sometimes, PHP is >= 5.4, but libxml is old enough that the constants are
    // not defined.
    static $new_libxml;
    if (!isset($new_libxml)) {
      $new_libxml = version_compare(PHP_VERSION, '5.4', '>=') && defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD');
    }
    if ($text != '') {
      $tables = [];
      libxml_use_internal_errors(TRUE);
      // LibXML requires that the html is wrapped in a root node.
      $text = '<root>' . $text . '</root>';
      // Process special characters
      $html = htmlspecialchars_decode(mb_encode_numericentity(htmlentities(trim($text), ENT_QUOTES, 'UTF-8'), [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
      $dom = new \DOMDocument();
      if ($new_libxml) {
       $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      }
      else {
        $dom->loadHTML($html);
      }

      $tables = $dom->getElementsByTagName('table');
      // Find all tables in text.
      if ($tables->length !== 0) {
        foreach ($tables as $table) {
          // Check if the table has a thead element #3244317. If the thead is
          // missing then skip any further modifications.
          $thead = iterator_to_array($table->getElementsByTagName('thead'));
          if (empty($thead)) {
            continue;
          }
          // Find existing class attributes, if any, and append tablesaw class.
          $existing_classes = $table->getAttribute('class');
          if (strpos($existing_classes, 'no-tablesaw') === FALSE) {
            $mode = $this->settings['tablesaw_type'] ?? 'stack';
            // Allow for class-based override of default.
            foreach (array_keys(self::$modes) as $mode_option) {
              if (strpos($existing_classes, "tablesaw-" . $mode_option) !== FALSE) {
                $mode = $mode_option;
                break;
              }
            }
            $new_classes = !empty($existing_classes) ? $existing_classes . ' tablesaw tablesaw-' . $mode : 'tablesaw tablesaw-' . $mode;
            $table->setAttribute('class', $new_classes);
            // Set data-tablesaw-mode & minimap.
            $table->setAttribute('data-tablesaw-mode', $mode);
            $table->setAttribute('data-tablesaw-minimap', '');
            $persist = $this->settings['tablesaw_persist'] ?? TRUE;
            $ths = $table->getElementsByTagName('th');
            $inc = 1;
            foreach ($ths as $delta => $th) {
              $th->setAttribute('role', 'columnheader');
              // Add required columntoggle- & swipe- specific attributes.
              if (in_array($mode, ['columntoggle', 'swipe'])) {
                $th->setAttribute('data-tablesaw-sortable-col', '');
                if (!$th->getAttribute('data-tablesaw-priority')) {
                  $th->setAttribute('data-tablesaw-priority', $inc);
                  $inc++;
                }
              }
              // Add persistent first column if no priority has been specified.
              if ($persist && $delta === 0 && !$th->getAttribute('data-tablesaw-priority')) {
                $th->setAttribute('data-tablesaw-priority', 'persist');
              }
            }
          }
        }
        // Get innerHTML of root node.
        $html = "";
        foreach ($dom->getElementsByTagName('root')->item(0)->childNodes as $child) {
          // Re-serialize the HTML.
          $html .= $dom->saveHTML($child);
        }
        // For lower older libxml, use preg_replace to clean up DOCTYPE.
        if (!$new_libxml) {
          $html_start = strpos($html, '<html><body>') + 12;
          $html_length = strpos($html, '</body></html>') - $html_start;
          $html = substr($html, $html_start, $html_length);
        }

        return $html;
      }
    }
    return FALSE;
  }

}
