import augmentorUI from './augmentorui';
import { Plugin } from 'ckeditor5/src/core';
import {ContextualBalloon} from 'ckeditor5/src/ui';

export default class augmentor extends Plugin {
  static get requires() {
    return [augmentorUI, ContextualBalloon];
  }
}
