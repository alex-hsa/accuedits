/**
 * @file
 * Defines JavaScript behaviors for the frontend editing settings form.
 */

(($, Drupal) => {
  /**
   * Behaviors for summaries for tabs in the frontend editing settings form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the frontend editing settings form.
   */
  Drupal.behaviors.frontendEditingFormSummaries = {
    attach(context) {
      $('.entity-type-tab', context).drupalSetSummary((tab) => {
        const enabled = $('input.entity-type-bundles:checked', tab);
        return enabled.length > 0 ? Drupal.t('Enabled') : '';
      });

      // Open nodes by default.
      $(
        'a[href="#edit-frontend-editing-entity-types-paragraph"]',
        context,
      ).trigger('click');
    },
  };
})(jQuery, Drupal);
