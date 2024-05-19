import {Plugin} from 'ckeditor5/src/core';
import {Widget} from 'ckeditor5/src/widget';
import InsertMaterialIconsCommand from './insertmaterialiconscommand';

export default class MaterialIconsEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }
  /**
   * @inheritdoc
   */
  init() {
    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
  }

  /**
   * Registers materialIcon as an element in the DOM converter.
   *
   * @private
   */
  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register('materialIcon', {
      allowWhere: '$text',
      isInline: true,
      isObject: true,
      allowAttributes: ['class'],
    });
  }

  /**
   * Defines handling of material icon element in the content.
   *
   * @private
   */
  _defineConverters() {
    const conversion = this.editor.conversion;

    // Allow class attribute.
    conversion.attributeToAttribute({model: 'class', view: 'class'});

    conversion.for('downcast')
      .elementToElement({
        model: 'materialIcon',
        view: 'span'
      });

    conversion.for('upcast')
      .elementToElement({
        view: {
          name: 'span',
          classes: 'material-icon',
        },
        model: 'materialIcon'
      });
  }

  /**
   * Defines the material icon insert command.
   *
   * @private
   */
  _defineCommands() {
    const editor = this.editor;
    editor.commands.add(
      'insertMaterialIcons',
      new InsertMaterialIconsCommand(this.editor),
    );
  }

}
