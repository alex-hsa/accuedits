/**
 * @file
 * Customizations to original Tablesaw library.
 *
 * If and when fixes are applied upstream, these changes can be removed.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.responsive_tables_filter = Drupal.responsive_tables_filter || {};
  Drupal.behaviors.facetsCheckboxReset = {
    attach: function (context) {
      if (window.Tablesaw !== 'undefined') {
        if (once('tablesaw-create', 'html').length) {
          $(window).on(Tablesaw.events.create, function (event, tablesaw) {
            Drupal.responsive_tables_filter.fixCellLabels(context);
          });
        }
      }
    }
  };

  /**
   * Find all Tablesaw-generated cell labels.
   */
  Drupal.responsive_tables_filter.fixCellLabels = function (context) {
    var $labels = $('b.tablesaw-cell-label');
    $labels.each(Drupal.responsive_tables_filter.makeElementAccessible);
  };

  /**
   * Replace all Tablesaw-generated b elements with strong.
   */
  Drupal.responsive_tables_filter.makeElementAccessible = function () {
    var replacement = document.createElement('strong');
    replacement.innerHTML = $(this).html();
    replacement.setAttribute('class', $(this).attr('class'));
    if ($(this).parent().is("th")) {
      replacement.setAttribute('aria-hidden', true);
    }
    $(this).replaceWith(replacement);
  };

})(jQuery, Drupal);
