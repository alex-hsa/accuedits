## Material Icons

Allows inserting Material Icons from either a field or inside CKEditor.

| f | 1.x | 2.x |
|--|-----|-----|
| Drupal Core | 8 or 9 | >=9.3 or 10   |
| Field Formatter | X    | X   |
| CKEditor 4 | X | X    |
| CKEditor 5 |  | X |


|                   | 1.x                | 2.x                    |
|-------------------|--------------------|------------------------|
| Core              | ^8 &#124;&#124; ^9 | >=9.3 &#124;&#124; ^10 |
| Field Formatter   | Y                  | Y                      |
| CKEditor 4        | Y                  | Y                      |
| CKEditor 5        | N                  | Y                      |


## INSTALLATION

Install the module per normal

FONT FAMILY:
1. Configure available icon styles at /admin/config/content/material_icons

FIELDS:
1. Add a new field to any entity type of type Material Icons

2. Configure the Form Display

CKEDITOR:
1. Select a text format to enable the CKEditor plugin for
at /admin/config/content/formats

2. Drag the Material Icons button from "Available buttons"
to "Active toolbar"

3. Ensure your text format is not limiting HTML, or has the ability
to have classes on <i> elements

4. Save the text format.

## MAINTAINERS

B_SHARPE https://www.drupal.org/u/b_sharpe
