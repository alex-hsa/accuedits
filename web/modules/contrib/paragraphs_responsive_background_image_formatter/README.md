# Paragraphs Responsive Background Image Formatter

## Summary

This module provides a responsive background image formatter for paragaphs. The module extends the Responsive Background
Image formatter module.

The formatter can be configured with the following options:

* DOM element target - either the <b>Paragraph</b> or the <b>Media field element</b> referenced within the paragraph.

Using the <b>Paragraph</b> setting, the background image is rendered on the paragraph container / behind the paragraph
child elements - useful for full screen paragraphs.

Using the <b>Media field element</b> setting, background images are rendered on the media field element - useful for
inline background elements.

## Requirements

- Paragraphs
- Responsive Background Image Formatter

## Installation

Install as you would normally install a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.

## Usage

* Create a paragraph
* Create a media field within the paragraph
* Set the field display setting for the media field to rendered entity
* Create or modify the media image display settings and choose <b>Paragraphs Responsive Background Image</b> as the
  formatter
* Set the <b>DOM element target</b> setting to either <b>Paragraph</b> or <b>Media field element</b>
