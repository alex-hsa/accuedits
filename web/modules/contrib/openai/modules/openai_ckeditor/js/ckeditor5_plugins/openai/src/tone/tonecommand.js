import { Command } from 'ckeditor5/src/core';
import FormView from './form';
import { ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';
import OpenAiRequest from "../api/request";

export default class ToneCommand extends Command {
    constructor(editor, config) {
      super(editor);
      this._balloon = this.editor.plugins.get( ContextualBalloon );
      this.formView = this._createFormView();
      this._config = config;
      this._request = this.editor.plugins.get( OpenAiRequest );
    }

    execute(options = {}) {
      this._showUI();
    }

    _createFormView() {
      const editor = this.editor;
      const formView = new FormView(editor.locale);

      this.listenTo( formView, 'submit', () => {
        const selection = editor.model.document.selection;
        const range = selection.getFirstRange();
        let selectedText = '';

        for (const item of range.getItems()) {
          if (typeof item.data !== undefined) {
            selectedText += item.data + ' ';
          }
        }

        if (!selectedText.length) {
          return;
        }

        const prompt = 'Change the tone of the following text to be ' + formView.toneInputView.fieldView.element.value + ' using the same language as the following text:\r\n' + selectedText;
        this._hideUI();
        this._request.doRequest('api/openai-ckeditor/completion', {'prompt': prompt, 'options': this._config});
      });

        // Hide the form view after clicking the "Cancel" button.
        this.listenTo(formView, 'cancel', () => {
          this._hideUI();
        } );

        // Hide the form view when clicking outside the balloon.
        clickOutsideHandler( {
          emitter: formView,
          activator: () => this._balloon.visibleView === formView,
          contextElements: [ this._balloon.view.element ],
          callback: () => this._hideUI()
        } );

        return formView;
      }

      _getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        let target = null;

        // Set a target position by converting view selection range to DOM.
        target = () => view.domConverter.viewRangeToDom(
          viewDocument.selection.getFirstRange()
        );

        return {
          target
        };
      }

      _showUI() {
        this._balloon.add( {
          view: this.formView,
          position: this._getBalloonPositionData()
        } );

        this.formView.focus();
      }

      _hideUI() {
        this.formView.toneInputView.fieldView.value = '';
        this.formView.element.reset();
        this._balloon.remove( this.formView );
        this.editor.editing.view.focus();
      }
}
