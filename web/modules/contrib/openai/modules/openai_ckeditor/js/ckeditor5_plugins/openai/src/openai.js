import OpenAIUI from './openaiui';
import NetworkStatus from './status/status';
import OpenAiRequest from "./api/request";
import { Plugin } from 'ckeditor5/src/core';
import {ContextualBalloon} from 'ckeditor5/src/ui';

export default class OpenAI extends Plugin {
  static get requires() {
    return [OpenAIUI, NetworkStatus, OpenAiRequest, ContextualBalloon];
  }
}
