(function ($) {
  $(document).ajaxSend(function (e, xhr, settings) {
  }).ajaxComplete(function (e, xhr, settings) {
    Tablesaw.init();
  });
})(jQuery);
