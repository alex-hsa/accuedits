# Augmentor

## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

## INTRODUCTION

Augmentor is a integration module which allows content to be augmented in Drupal
via connections with external services.

## REQUIREMENTS

* [Key](https://www.drupal.org/project/key).

## SUB MODULES

 * Augmentor Demo

## INSTALLATION

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

 * The Augmentor Demo submodule serves as a blueprint for developers.
   You can enabled this for your testing purposes.
  
## HOOKS

The Augmentor module introduces two hooks for Drupal 10 developers, enabling custom alterations before and after the execution of the augmentor process. These hooks are designed to offer flexibility in modifying request data and the results of the augmentor execution.

### 1. `hook_pre_execute(array &$request_body)`

This hook is invoked before the augmentor processing begins. It allows other modules to alter the decoded request body.

#### Parameters:
- `$request_body` (array): The decoded request body. It is an associative array that contains the keys 'input' and 'augmentor'.

#### Usage:
Implement this hook to modify the request body before the augmentor processes it. For example, you can add, remove, or alter the contents of `$request_body`.

Example:
```php
function mymodule_pre_execute(array &$request_body) {
  // Modify the request body as needed.
  if (isset($request_body['input'])) {
    $request_body['input'] = "Modified Input";
  }
}
```

### 2. `hook_post_execute(array &$result)`

This hook is invoked after the augmentor execution is completed. It allows other modules to alter the results of the execution.

#### Parameters:
- `$result` (array): The results of the augmentor execution. It is an associative array of the results.

#### Usage:
Implement this hook to modify the results after the augmentor execution. For example, you can process or reformat the results as needed.

Example:
```php
function mymodule_post_execute(array &$result) {
  // Process or alter the results.
  if (!empty($result)) {
    $result['additional_info'] = "Processed Result";
  }
}
```

### Notes:
- Ensure that your module implements these hooks correctly, as improper usage can lead to unexpected behaviors.
- Test the implementation thoroughly with various scenarios to ensure compatibility and stability.
- Consult the Drupal API documentation for more information and best practices related to hook implementation.

For more detailed information and examples, visit the Drupal API documentation at [Drupal API - Hooks](https://api.drupal.org/api/drupal/core%21core.api.php/group/hooks/10).

## CONFIGURATION


 * Configure the user permissions in Administration » People » Permissions:

   - Administer augmentors

     Users with this permission will see the webservices > augmentors
     configuration list page. From here they can add, configure, delete, enable
     and disabled augmentors.

     Warning: Give to trusted roles only; this permission has security
     implications. Allows full administration access to create and edit
     augmentors.


## MAINTAINERS

This module is maintained by developers at Morpht. For more information on the
company and our offerings, see [morpht.com](https://morpht.com/).

Current maintainers:
 * Eleo Basili (eleonel) - https://www.drupal.org/u/eleonel
 * Naveen Valecha (naveenvalecha) - https://www.drupal.org/u/naveenvalecha
