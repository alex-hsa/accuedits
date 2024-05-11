/**
* @file
* Drupal augmentor_ckeditor5 plugin.
*/

import {Plugin} from 'ckeditor5/src/core';
import {DropdownButtonView, Model, addListToDropdown, createDropdown} from 'ckeditor5/src/ui';
import icon from '../../../../icons/augmentor.svg';
import { Collection } from 'ckeditor5/src/utils';
import executeCommand from './execute/executecommand';

export default class augmentorUI extends Plugin {

  init() {
    const editor = this.editor;
    const augmentors = this.editor.config.get('augmentors')[0].augmentors;
    editor.commands.add('executeCommand', new executeCommand(editor, augmentors));

    editor.ui.componentFactory.add( 'augmentor', locale => {
      const items = new Collection();

      Object.keys(augmentors).forEach(augmentorUuid => {
        items.add({
          type: 'button',
          model: new Model({
              id: augmentorUuid,
              label: augmentors[augmentorUuid],
              withText: true,
              command: 'executeCommand',
          } )
        });
      });

      const dropdownView = createDropdown( locale, DropdownButtonView );
      addListToDropdown( dropdownView, items );

      dropdownView.buttonView.set({
        label: 'Augmentors',
        class: 'augmentor-dropdown',
        icon,
        tooltip: true,
        withText: true,
      });

      this.listenTo(dropdownView, 'execute', (eventInfo) => {
        var editor_id = this.editor.sourceElement.id;
        jQuery('#' + editor_id).before(Drupal.theme.ajaxProgressIndicatorFullscreen());
        this.editor.execute(eventInfo.source.command, eventInfo.source.id);
      });

      return dropdownView;
    });

  }
}
