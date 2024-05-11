import { Command } from 'ckeditor5/src/core';
import NetworkStatus from "../status/status";

export default class ReformatHTMLCommand extends Command {
  constructor(editor, config) {
    super(editor);
    this._config = config;
    this._status = this.editor.plugins.get( NetworkStatus );
  }

  execute(options = {}) {
    const editor = this.editor;
    const selection = editor.model.document.selection;
    const range = selection.getFirstRange();
    const status = this._status;
    let selectedText = '';

    for (const item of range.getItems()) {
      if (typeof item.data !== undefined) {
        selectedText += item.data + ' ';
      }
    }

    if (!selectedText.length) {
      return;
    }

    const prompt = 'Please fix this text to be marked up with semantic HTML using only lists, headers, or paragraph tags: ' + selectedText;

    status.fire('openai_status', {
      status: 'Waiting for response...'
    });

    editor.model.change( writer => {
      fetch(drupalSettings.path.baseUrl + 'api/openai-ckeditor/completion', {
        method: 'POST',
        credentials: 'same-origin',
        body: JSON.stringify({'prompt': prompt, 'options': this._config}),
      })
        .then((response) => {
          if (!response.ok) {
            status.fire('openai_status', {
              status: 'An error occurred. Check the logs for details.'
            });
          } else {
            status.fire('openai_status', {
              status: 'Receiving response...'
            });

            response.text().then((result) => {
              status.fire('openai_status', {
                status: 'Writing response...'
              });
              this._writeHTML(result, range);
            }).then(() => {
              setTimeout(() => {
                status.fire('openai_status', {status: 'Idle'});
              }, 3000);
            });
          }
        })
    } );
  }

  _writeHTML(html, range) {
    const editor = this.editor;
    const viewFragment = editor.data.processor.toView( html );
    const modelFragment = editor.data.toModel( viewFragment );
    editor.model.insertContent(modelFragment, range);
  }
}
