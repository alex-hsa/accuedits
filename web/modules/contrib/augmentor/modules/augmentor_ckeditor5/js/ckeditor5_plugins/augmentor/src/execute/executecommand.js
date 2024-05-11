import { Command } from 'ckeditor5/src/core';

export default class ExecuteCommand extends Command {
  constructor(editor, config) {
    super(editor);
    this._config = config;
  }

  execute(augmentor_id = {}) {
    const editor = this.editor;

    const selection = editor.model.document.selection;
    const selectionLastPosition = selection.getLastPosition();
    const ranges = selection.getRanges();
    let selectedText = '';

    for (let range of ranges) {
      for (let item of range.getItems()) {
        if (typeof item.data != 'undefined') {
          selectedText = selectedText + item.data + ' ';
        }
      }
    }

    var options = {
      'input': selectedText,
      'augmentor': augmentor_id,
      'type': 'ckeditor',
    };

    editor.model.change(writer => {
      fetch(drupalSettings.path.baseUrl + 'augmentor/execute/augmentor', {
        method: 'POST',
        credentials: 'same-origin',
        body: JSON.stringify(options),
      })
        .then((response) => {
          jQuery('.ajax-progress--fullscreen').remove();

          if (response.ok) {
            return response.json();
          }
          this._showError(JSON.parse(result.responseJSON));
        })
        .then((result) => this._updateCkeditor(result, selectionLastPosition))
        .catch((error) => {
          this._showError(error)
        });
    } );
  }

  _updateCkeditor(result, position) {
    var output = JSON.parse(result);
    output = "<br/>" + output.default.toString();
    // Replace "\n" with "<br/>" as Ckeditor5 doesn't support \n
    output = output.replaceAll("\n", "<br/>");
    const editor = this.editor;
    const viewFragment = editor.data.processor.toView( output );
    const modelFragment = editor.data.toModel( viewFragment );
    editor.model.insertContent(modelFragment, position);
  }

  _showError(error) {
    const messages = new Drupal.Message();
    messages.clear();
    messages.add(error, { type: 'error' });
    jQuery("html, body").animate({ scrollTop: 0 }, "slow");
  }
}
