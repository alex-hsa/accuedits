(function ($, Drupal) {
  Drupal.behaviors.smartmenusBehaviour = {
    attach: function (context, settings) {
      $(once('smartmenus-behavior', '.sm', context)).each(function () {
          $(this).smartmenus(
            {
              mainMenuSubOffsetX: -1,
              mainMenuSubOffsetY: 4,
              subMenusSubOffsetX: 6,
              subMenusSubOffsetY: -6
            }
          );
      });

      // https://www.smartmenus.org/docs/#menu-toggle-button
      $(once('smartmenus-menu-state', '.sm-menu-state', context)).each(function () {
        var $mainMenuState = $(this);
        // animate mobile menu
        $mainMenuState.change(function(e) {
          var $menu = $("ul[data-drupal-selector='smartmenu']");
          console.log($menu);
          if (this.checked) {
            $menu.hide().slideDown(250, function() { $menu.css('display', ''); });
          } else {
            $menu.show().slideUp(250, function() { $menu.css('display', ''); });
          }
        });
        // hide mobile menu beforeunload
        $(window).bind('beforeunload unload', function() {
          if ($mainMenuState[0].checked) {
            $mainMenuState[0].click();
          }
        });
      });

    }
  };
})(jQuery, Drupal);
