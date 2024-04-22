(function (window) {
  /**
   * Listen for postMessage events from the iframe.
   */
  window.addEventListener(
    'message',
    function (event) {
      // Do we trust the sender of this message?
      if (event.origin !== window.location.origin) {
        return;
      }
      if (event.data === '' || event.data.length === 0) {
        return;
      }
      // Check that it is the message from frontend editing.
      if (typeof event.data.command === 'undefined') {
        return;
      }
      if (event.data.selector) {
        // Close side panel and update content if possible.
        if (event.data.command === 'closeSidePanel') {
          const selector = event.data.selector.replace(
            'data-frontend-editing',
            'data-fe-update-content',
          );
          const element = document.querySelector(selector);
          if (element) {
            element.click();
          } else {
            window.location.reload();
          }
        }
        // Preview the changes.
        if (event.data.command === 'feEntityPreview') {
          if (event.data.realUuid) {
            // First check whether the element was already previewed.
            let element = document.querySelector(
              `[data-preview="${event.data.realUuid}"]`,
            );
            if (!element) {
              element = document.querySelector(event.data.selector);
              element.dataset.preview = event.data.realUuid;
            }
            Drupal.ajax({ url: event.data.url }).execute();
          } else {
            const selector = event.data.selector.replace(
              'data-preview',
              'data-fe-preview-content',
            );
            const element = document.querySelector(selector);
            if (element) {
              element.click();
            }
          }
        }
      }
    },
    false,
  );
})(window);
