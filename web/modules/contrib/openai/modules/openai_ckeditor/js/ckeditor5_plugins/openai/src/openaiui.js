/**
 * @file registers the OpenAI Completion button and binds functionality to it.
 */

import {Plugin} from 'ckeditor5/src/core';
import {DropdownButtonView, Model, addListToDropdown, createDropdown} from 'ckeditor5/src/ui';
import icon from '../../../../icons/openai.svg';
import { Collection } from 'ckeditor5/src/utils';
import CompletionCommand from './completion/completioncommand';
import HelpCommand from "./help/helpcommand";
import TranslateCommand from "./translate/translatecommand";
import ToneCommand from './tone/tonecommand';
import SummarizeCommand from './summarize/summarizecommand';
import ReformatHTMLCommand from "./reformat_html/reformathtmlcommand";

export default class OpenAIUI extends Plugin {

  init() {
    const editor = this.editor;
    const config = this.editor.config.get('openai_ckeditor_openai');

    editor.commands.add('CompletionCommand', new CompletionCommand(editor, config.completion));
    editor.commands.add('TranslateCommand', new TranslateCommand(editor, config.completion));
    editor.commands.add('ToneCommand', new ToneCommand(editor, config.completion));
    editor.commands.add('SummarizeCommand', new SummarizeCommand(editor, config.completion));
    editor.commands.add('HelpCommand', new HelpCommand(editor));
    editor.commands.add('ReformatHTMLCommand', new ReformatHTMLCommand(editor, config.completion));

    editor.ui.componentFactory.add( 'openai', locale => {
      const items = new Collection();

      // @todo: loop Enabled plugins and add them as items with their configuration
      items.add( {
        type: 'button',
        model: new Model( {
            isEnabled: config.completion.enabled,
            label: 'Text Completion',
            withText: true,
            command: 'CompletionCommand',
            group: config.completion
        } )
      });

      items.add( {
        type: 'button',
        model: new Model( {
          isEnabled: config.completion.enabled,
          label: 'Adjust tone/voice',
          withText: true,
          command: 'ToneCommand',
          group: config.completion
        } )
      });

      items.add( {
        type: 'button',
        model: new Model( {
            isEnabled: config.completion.enabled,
            label: 'Summarize',
            withText: true,
            command: 'SummarizeCommand',
            group: config.completion
        } )
      });

      items.add( {
        type: 'button',
        model: new Model( {
            isEnabled: config.completion.enabled,
            label: 'Translate',
            withText: true,
            command: 'TranslateCommand',
            group: config.completion
        } )
      });

      items.add( {
        type: 'button',
        model: new Model( {
          isEnabled: config.completion.enabled,
          label: 'Reformat/correct HTML',
          withText: true,
          command: 'ReformatHTMLCommand',
          group: config.completion
        } )
      });

      //
      // items.add( {
      //   type: 'button',
      //   model: new Model( {
      //       isEnabled: false,
      //       label: 'Sentiment analysis',
      //       withText: true,
      //       command: '',
      //       group: {}
      //   } )
      // });

      items.add( {
        type: 'button',
        model: new Model( {
          label: 'Help & Support',
          withText: true,
          command: 'HelpCommand',
          group: {}
        } )
      });

      const dropdownView = createDropdown( locale, DropdownButtonView );

      // Create a dropdown with a list inside the panel.
      addListToDropdown( dropdownView, items );

      // Attach the dropdown menu to the dropdown button view.
      dropdownView.buttonView.set( {
        label: 'OpenAI',
        class: 'openai-dropdown',
        icon,
        tooltip: true,
        withText: true,
      });

      this.listenTo(dropdownView, 'execute', (evt) => {
        this.editor.execute(evt.source.command, evt.source.group);
      });

      return dropdownView;
    });

  }
}
