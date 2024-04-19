# Smartmenus jQuery Plugin - Drupal module

This module provides Drupal integration with the Smartmenus.js advanced jQuery
website menu plugin. Mobile first, responsive and accessible list-based website
menus that work on all devices. This is the library that was included in core
for BackdropCMS, and is most commonly used with Bootstrap.


## INSTALLATION

1) Install the Smartmenus jQuery plugin.

  Smartmenus library is added using composer.

  Add this to your project's composer.json:

```
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "drmonty/smartmenus",
                "version": "1.1.1",
                "type": "drupal-library",
                "dist": {
                    "url": "https://www.smartmenus.org/files/?file=smartmenus-jquery/smartmenus-1.1.1.zip",
                    "type": "zip"
                },
                "require": {
                    "composer/installers": "^1.2"
                }
            }
        }
    ],
```

2) Install the Smartmenus module as usual (see below)
   http://drupal.org/project/smartmenus


## USAGE

1) Navigate to the blocks administration at admin/structure/blocks

2) Place the Responsive Main Menu (Smartmenus) block into a region

3) Select necessary options from the block configure form.

4) Check the UI.


## REQUIREMENTS

-  Smartmenus jQuery Plugin (https://www.smartmenus.org)

## MAIN CONTRIBUTORS

- jQuery Plugin created by Vasil Dinkov (https://github.com/vadikom)

-  Drupal 7 module created by Jen Lampton (http://drupal.org/user/85586)

-  Drupal 8 module ported by Vishwa Chikate (https://www.drupal.org/user/2857973)
