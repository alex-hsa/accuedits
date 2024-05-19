/**
 * @file registers the Material Icons toolbar button and binds functionality to
 *   it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from "../../../../icons/materialicons.svg";

export default class MaterialIconsUI extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('materialIcons');
    if (!options) {
      return;
    }

    const {openDialog, dialogSettings = {}} = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // This will register the simpleBox toolbar button.
    editor.ui.componentFactory.add('materialIcons', (locale) => {
      const command = editor.commands.get('insertMaterialIcons');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Material Icons'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          Drupal.url('material_icons/dialog'),
          ({ settings }) => {
            editor.execute('insertMaterialIcons', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}
