# TOCBOT DRUPAL MODULE

## INTRODUCTION

This module provides a wrapper around Tocbot which builds an automatic table of
contents (TOC) from headings in an HTML document. This is useful for
documentation websites or long markdown pages because it makes them easier
to navigate.

- A block is created that will contain the automatic table of contents, you can place
it in the Block layout page to work out of the box.
- You can configure most of the Javascript settings from Drupal administration (such as
where to look to grab headings).
- A minimum limit can be set, so it won't activate on simple pages (default 3).
- Uses css/js from a CDN by default, but you can provide library files if
you want to host those locally.

## REQUIREMENTS

This module has versions for Drupal 7, 8 and 9.

## INSTALLATION

### CDN BY DEFAULT

This module will load the tocbot library by CDN by default, if you want to
host it locally read the LOCAL INSTALLATION section.

### LOCAL INSTALLATION

If you place a copy of the CDN files into your /library folder this module
will use those instead of the CDN versions. Find the latest release
at https://github.com/tscanlin/tocbot/releases/

- https://cdnjs.cloudflare.com/ajax/libs/tocbot/4.12.2/tocbot.min.js
  - /libraries/tocbot/js/tocbot.min.js
- https://cdnjs.cloudflare.com/ajax/libs/tocbot/4.12.2/tocbot.css
  - /libraries/tocbot/css/tocbot.css

## CONFIGURATION

The Tocbot module admin interface is located at
`admin/config/content/tocbot` more explanation of those settings can be
found at [tscanlin.github.io/tocbot/#api](https://tscanlin.github.io/tocbot/#api)
