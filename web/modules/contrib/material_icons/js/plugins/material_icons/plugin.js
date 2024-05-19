/**
 * @file
 * Bootstrap Grid plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('material_icons', {
    icons: 'material_icons',
    init: function (editor) {

      // Add the dialog command.
      editor.addCommand('material_icons', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec: function (editor) {
          // Fired when saving the dialog.
          var saveCallback = function saveCallback(returnValues) {
            var settings = returnValues.settings || false;
            if (!settings) return;
            editor.fire('saveSnapshot');

            var selection = editor.getSelection();
            var range = selection.getRanges(1)[0];

            var container = new CKEDITOR.dom.element('i', editor.document);

            const fontClassName = (fontFamily) => {
              const familySplit = fontFamily.split('__');
              const fontSet = (familySplit.length > 1) ? familySplit[0] : 'icons';
              const fontFamilyType = familySplit.pop();
              return `material-${fontSet}-${fontFamilyType}`;
            }

            if (settings.family === 'baseline') {
              container.addClass('material-icons');
            }
            else {
              container.addClass(fontClassName(settings.family));
            }

            // Add other classes.
            if (settings.classes !== '') {
              container.addClass(settings.classes)
            }
            container.setText(settings.icon);

            range.insertNode(container);
            range.select();

            editor.fire('saveSnapshot');
          };

          var dialogSettings = {
            dialogClass: 'material-icons-dialog',
          };

          // Open the entity embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, Drupal.url('material_icons/dialog'), {}, saveCallback, dialogSettings);
        }
      });

      // UI Button
      editor.ui.addButton('material_icons', {
        label: 'Insert Material Icons',
        command: 'material_icons',
        icon: this.path + 'icons/material_icons.png'
      });

    }
  });

})(jQuery, Drupal, CKEDITOR);
