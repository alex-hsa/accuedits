<?php

/**
 * @file
 * Describe hooks provided by the augmentor module.
 */

/**
 * Alters the input configuration before it is passed to the augmentor.
 *
 * @param array $decoded_request_body
 *   The decoded request body.
 */
function hook_pre_execute(array &$decoded_request_body) {
}

/**
 * Alters the results of the augmentor execution.
 *
 * @param array $results
 *   The results of the augmentor execution.
 */
function hook_post_execute(array &$result) {
}
