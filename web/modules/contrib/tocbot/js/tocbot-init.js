(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tocbot = {
    attach: function attachTocBot(context, settings) {
      var options = settings.tocbot;
      var content = document.querySelector(options.contentSelector);
      if(content === null) {
        return;
      }

      if (options.createAutoIds) {
        // Create automatic ids
        var headings = content.querySelectorAll(options.headingSelector);
        var headingMap = {};

        Array.prototype.forEach.call(headings, function (heading) {
          var id = heading.id ? heading.id : heading.textContent.trim().toLowerCase()
            .split(' ').join('-').replace(/[\!\@\#\$\%\^\&\*\(\)\:]/ig, '');
          headingMap[id] = !isNaN(headingMap[id]) ? ++headingMap[id] : 0
          if (headingMap[id]) {
            heading.id = id + '-' + headingMap[id]
          } else {
            heading.id = id
          }
        });
      }

      var headings = $(options.contentSelector + ' :header').not(options.ignoreSelector);
      if ($(options.tocSelector).length && headings.length >= parseInt(options.minActivate)) {
        // Activate
        if (options.extraBodyClass.length > 0) {
          $('body').addClass(options.extraBodyClass);
        }

        // Fix tocbot offsettop bug
        if (options.fixedSidebarOffset === "auto") {
          var element = document.querySelector(options.tocSelector);
          var yPosition = 0;
          while (element) {
            yPosition +=
              element.offsetTop -
              element.scrollTop +
              element.clientTop;
            element = element.offsetParent;
          }
          options.fixedSidebarOffset = yPosition;
        }

        // Initialize tocbot
        tocbot.init(options);
      }
    },
  };
})(jQuery, Drupal);
