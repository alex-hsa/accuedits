import MaterialIconsEditing from "./materialiconsediting";
import MaterialIconsUi from "./materialiconsui";
import { Plugin } from 'ckeditor5/src/core';

class MaterialIcons extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [MaterialIconsEditing, MaterialIconsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'MaterialIcons';
  }
}

export default {
  MaterialIcons,
};
