/**
 * Implements ajax form.
 */

(function (Drupal, $) {
  /**
   * Implements cancel frontend editing behaviour.
   *
   * @type {{attach: Drupal.behaviors.cancelFrontendEditing.attach}}
   */
  Drupal.behaviors.cancelFrontendEditing = {
    attach(context, settings) {
      const cancelButton = context.querySelector('#edit-cancel');
      if (!cancelButton || cancelButton.length === 0) {
        return;
      }
      cancelButton.addEventListener('click', function (e) {
        e.preventDefault();
        // Close the side panel.
        Drupal.AjaxCommands.prototype.closeSidePanel({}, {}, 'success');
      });
    },
  };

  /**
   * Ajax command closeSidePanel.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.closeSidePanel = function (
    ajax,
    response,
    status,
  ) {
    if (status === 'success') {
      if (!response.selector) {
        // Reload the page.
        window.parent.location.reload();
      } else {
        window.parent.postMessage(response, window.location.origin);
      }
      // Remove iframe while we wait for the reload.
      window.parent.document.getElementById('editing-container').remove();
    }
  };

  if (typeof Drupal.AjaxCommands.prototype.scrollTop === 'undefined') {
    /**
     * Command to scroll the page to an html element.
     *
     * @param {Drupal.Ajax} [ajax]
     *   A {@link Drupal.ajax} object.
     * @param {object} response
     *   Ajax response.
     * @param {string} response.selector
     *   Selector to use.
     */
    Drupal.AjaxCommands.prototype.scrollTop = function (ajax, response) {
      const offset = $(response.selector).offset();
      // We can't guarantee that the scrollable object should be
      // the body, as the element could be embedded in something
      // more complex such as a modal popup. Recurse up the DOM
      // and scroll the first element that has a non-zero top.
      let scrollTarget = response.selector;
      while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
        scrollTarget = $(scrollTarget).parent();
      }
      // Only scroll upward.
      if (offset.top - 10 < $(scrollTarget).scrollTop()) {
        $(scrollTarget).animate({ scrollTop: offset.top - 10 }, 500);
      }
    };
  }
})(Drupal, jQuery);
