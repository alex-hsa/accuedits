/**
 * @file
 * Drupal augmentor_ckeditor plugin.
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('augmentor_ckeditor', {
    requires: ['richcombo'],

    init: function (editor) {
      var config = editor.config;
      var data = editor.getData();
      const augmentors = config.augmentor_ckeditor;
      const augmentor_url = config.augmentor_url;
      // Setup Augmentors options list combo.
      editor.ui.addRichCombo('augmentor_ckeditor', {
        label: "Augmentors",
        title: 'Select Augmentor',
        multiSelect: true,
        panel: {
          css: [CKEDITOR.skin.getPath('editor')].concat(config.contentsCss),
        },

        init: function () {
          // Add search input filter. @see https://stackoverflow.com/a/36813060.
          this.add('search', '<div onmouseover="parent.comboSearch(this);" onclick="parent.nemsComboSearch(this);"><input class="cke_search" placeholder="Search"/></div>', '');

          // Add all the available augmentors.
          for (let index = 0; index < augmentors.length; ++index) {
            const augmentor = augmentors[index];
            this.add(augmentor.value, augmentor.label, augmentor.label);
          }

          // Add custom filter to select very specific elements in the DOM.
          if (!jQuery.expr[':'].icontains) {
            jQuery.expr[':'].icontains = function (a, i, m) {
              return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
            };
          }

          // Handler for the "search input filter".
          window.comboSearch = function (element) {
            var anchorID = $(element).closest('a').attr("id");
            var liDom = $(element).closest('li');

            liDom.empty().append('<div id="' + anchorID + '" style="padding:4px 5px;"><input class="cke_search" placeholder="Search" /></div>');

            liDom.find('input').off("keyup").on("keyup", function () {
              var data = this.value;
              // Create a jquery object of the augmentors.
              var jo = liDom.siblings('li');

              filter.call(this, data, jo);

            }).focus(function () {
              this.value = "";
              $(this).unbind('focus');
            });
          };

          // Alter the augmentors by filtering options using a given search input.
          function filter(data, jo) {
            if (this.value === "") {
              jo.show();
              return;
            }
            // Hide all the augmentors.
            jo.hide();

            // Recusively filter the jquery object to get results.
            jo.filter(function (i, v) {
              var $t = $(this);
              if ($t.is(":icontains('" + data + "')")) {
                return true;
              }
              return false;
            }).show();
          }
        },

        onClick: function (value) {
          $(editor.element.$).before(Drupal.theme.ajaxProgressIndicatorFullscreen());
          var input = editor.getSelection().getSelectedText();
          var options = {
            'input': input,
            'augmentor': value,
            'type': 'ckeditor',
          };

          $.ajax({
            url: augmentor_url,
            type: "POST",
            data: JSON.stringify(options),
            dataType: "json",
            beforeSend: function (x) {
              if (x && x.overrideMimeType) {
                x.overrideMimeType("application/json;charset=UTF-8");
              }
            },
            success: function (result) {
              $('.ajax-progress--fullscreen').remove();
              var output = JSON.parse(result);
              output = output.default.toString()
              var outputOptions = output.split('\n');
              var newData = editor.getData();

              for (let i = 0; i < outputOptions.length; i++) {
                if (outputOptions[i].trim()) {
                  var newData = newData + '<p>' + outputOptions[i] + '</p>';
                }
              }

              editor.setData(newData);
            },
            error: function (result) {
              $('.ajax-progress--fullscreen').remove();
              var parsed_result = JSON.parse(result.responseJSON);
              const messages = new Drupal.Message();
              messages.clear();
              messages.add(parsed_result, { type: 'error' });
              $("html, body").animate({ scrollTop: 0 }, "slow");
            }
          });
        },
      });
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
