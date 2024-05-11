<?php

namespace Drupal\augmentor_search_api_processors\Plugin\search_api\processor;

/**
 * Executes a given augmentor during the query preprocess.
 *
 * @SearchApiProcessor(
 *   id = "augmentor_preprocess_query",
 *   label = @Translation("Augmentor Preprocess Query"),
 *   description = @Translation("Executes a given augmentor during the query preprocess."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_query" = -10,
 *   }
 * )
 */
class AugmentorPreprocessQuery extends AugmentorPreprocessIndex {}
