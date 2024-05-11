import { Command } from 'ckeditor5/src/core';
import FormView from './form';
import { ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';
import OpenAiRequest from "../api/request";

export default class CompletionCommand extends Command {
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
        const prompt = formView.promptInputView.fieldView.element.value;
        this._hideUI();

        if (!prompt.length) {
          return;
        }

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
      this.formView.promptInputView.fieldView.value = '';
      this.formView.element.reset();
      this._balloon.remove( this.formView );
      this.editor.editing.view.focus();
    }
}
