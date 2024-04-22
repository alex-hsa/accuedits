/**
 * Implements preview.
 */
(function (Drupal, $, once) {
  /**
   * Implements automatic preview behaviour.
   */
  Drupal.behaviors.autoPreview = {
    attach(context, settings) {
      once(
        'autoPreview',
        '.frontend-editing-automatic-preview',
        context,
      ).forEach(function (element) {
        const form = element.closest('form');
        const previewButton = form.querySelector(
          '[data-drupal-selector="edit-preview"]',
        );
        const debounceTime = 300;

        // Listen to changes in the form and autocomplete fields.
        $(form).on(
          'formUpdated autocompleteclose',
          Drupal.debounce(function () {
            triggerPreview();
          }, debounceTime),
        );

        // Handle drop events in draggable tables.
        if (Drupal.tableDrag) {
          Drupal.tableDrag.prototype.onDrop = Drupal.debounce(function () {
            triggerPreview();
          }, debounceTime);
        }

        // Trigger preview if automatic preview is enabled.
        function triggerPreview() {
          if (element.checked) {
            // Prevent formUpdated behavior execution after triggering the preview.
            Drupal.behaviors.formUpdated.detach(context, settings, 'unload');
            if (typeof Drupal.file !== 'undefined') {
              $(previewButton).off('mousedown', Drupal.file.disableFields);
            }
            // Prevent preview button from taking the focus.
            previewButton.setAttribute('data-disable-refocus', true);
            previewButton.dispatchEvent(new Event('mousedown'));
          }
        }
      });
    },
  };

  /**
   * Ajax command feEntityPreview.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.feEntityPreview = function (
    ajax,
    response,
    status,
  ) {
    if (status === 'success') {
      const messages = new Drupal.Message();
      messages.clear();
      if (!response.selector) {
        // Reload the page.
        window.parent.location.reload();
      } else {
        window.parent.postMessage(response, window.location.origin);
      }
    }
  };
})(Drupal, jQuery, once);
