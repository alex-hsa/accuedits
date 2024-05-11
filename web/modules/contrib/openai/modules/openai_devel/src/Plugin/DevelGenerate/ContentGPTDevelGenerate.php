<?php

namespace Drupal\openai_devel\Plugin\DevelGenerate;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drush\Utils\StringUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\devel_generate\Plugin\DevelGenerate\ContentDevelGenerate;
use OpenAI\Client;

/**
 * Provides a ContentGPTDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "content_gpt",
 *   label = @Translation("content from ChatGPT"),
 *   description = @Translation("Generates content using OpenAI's ChatGPT. Optionally delete current content."),
 *   url = "content-gpt",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 15,
 *     "kill" = FALSE,
 *     "max_comments" = 0,
 *     "add_type_label" = FALSE
 *   },
 *   dependencies = {
 *     "node",
 *   },
 * )
 */
class ContentGPTDevelGenerate extends ContentDevelGenerate {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->client = $container->get('openai.client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['gpt'] = [
      '#type' => 'details',
      '#title' => t('GPT Options'),
      '#description' => t('Set various options related to how ChatGPT generates text.'),
      '#open' => TRUE,
      '#weight' => -50,
    ];

    $form['gpt']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => [
        'gpt-4-1106-preview' => 'gpt-4-1106-preview',
        'gpt-4-vision-preview' => 'gpt-4-vision-preview',
        'gpt-4' => 'gpt-4',
        'gpt-4-32k' => 'gpt-4-32k',
        'gpt-3.5-turbo-1106' => 'gpt-3.5-turbo-1106',
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
        'gpt-3.5-turbo-0301' => 'gpt-3.5-turbo-0301',
      ],
      '#default_value' => 'gpt-3.5-turbo',
      '#description' => $this->t('Select which model to use to generate text. See the <a href=":link">model overview</a> for details about each model.', [':link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['gpt']['system'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Profile'),
      '#default_value' => 'Your task is to generate content. I would like you to generate content about content management systems.',
      '#description' => $this->t('The "system profile" helps set the behavior of the ChatGPT response. You can change/influence how it response by adjusting the above instruction. Try adding instructions related to the types of content you wish to generate for different results.'),
      '#required' => TRUE,
    ];

    $form['gpt']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .1,
      '#default_value' => '0.4',
      '#description' => $this->t('What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.'),
    ];

    $form['gpt']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max tokens'),
      '#min' => 128,
      '#step' => 1,
      '#default_value' => '512',
      '#description' => $this->t('The maximum number of tokens to generate in the response. The token count of your prompt plus max_tokens cannot exceed the model\'s context length.'),
    ];

    $form['gpt']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('HTML formatted'),
      '#default_value' => FALSE,
      '#description' => $this->t('If TRUE, OpenAI will be instructed to format the replies in basic HTML format for text formatted fields. Warning, this will consume many more tokens in the response.'),
    ];

    $form['base_fields']['#required'] = TRUE;
    $form['base_fields']['#description'] = $this->t('Enter the field names as a comma-separated list. These will be populated. Please note generating text with GPT will only work on string/text type fields!');

    // with GPT, this isn't really needed
    unset($form['title_length']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    parent::settingsFormValidate($form, $form_state);
    $model = $form_state->getValue('model');
    $max_tokens = (int) $form_state->getValue('max_tokens');

    switch ($model) {
      case 'gpt-4':
      case 'gpt-4-0314':
        if ($max_tokens > 8192) {
          $form_state->setError($form['gpt']['max_tokens'], $this->t('The model you have selected only supports a maximum of 8192 tokens. Please reduce the max token value to 8192 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo':
      case 'gpt-3.5-turbo-0301':
        if ($max_tokens > 4096) {
          $form_state->setError($form['gpt']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4096 tokens. Please reduce the max token value to 4096 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo-16k':
        if ($max_tokens > 16384) {
          $form_state->setError($form['gpt']['max_tokens'], $this->t('The model you have selected only supports a maximum of 16384 tokens. Please reduce the max token value to 16384 or lower.'));
        }
        break;
      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []) {
    $values = parent::validateDrushParams($args, $options);

    $values['temperature'] = (float) $options['temperature'];
    $values['max_tokens'] = (int) $options['max_tokens'];
    $values['model'] = $options['model'];
    $values['system'] = $options['system'];
    $values['html'] = (bool) $options['html'];

    if (!str_contains($values['model'], 'gpt-4') && $values['max_tokens'] > 4096) {
      throw new \Exception(dt('The max tokens limit for GPT-3 models is 4096. Please enter a value equal to or lower than 4096.'));
    }

    if (empty($values['system'])) {
      throw new \Exception(dt('Please provide the --system option so OpenAI GPT understands the kind of responses you want to receive back.'));
    }

    if (empty($values['base_fields'])) {
      throw new \Exception(dt('Please provide the --base-fields option. This is required so Drupal knows which fields to use GPT for (title is already assumed).'));
    }

    if ($values['temperature'] < 0 || $values['temperature'] > 2) {
      throw new \Exception(dt('The value for temperature must be a value between 0 and 2.'));
    }

    if ($values['max_tokens'] < 0) {
      throw new \Exception(dt('Max tokens must be greater than 0.'));
    }

    if ($this->isBatch($values['num'], $values['max_comments'])) {
      $this->drushBatch = TRUE;
      $this->develGenerateContentPreNode($values);
    }

    return $values;
  }

  /**
   * Always batch the operations.
   */
  protected function isBatch($content_count, $comment_count) {
    return TRUE;
  }

  /**
   * Create one node. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   Results information.
   */
  protected function develGenerateContentAddNode(array &$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }

    $users = $results['users'];

    $system = $results['system'];
    $model = $results['model'];
    $temperature = (float) $results['temperature'];
    $max_tokens = (int) $results['max_tokens'];

    if (is_array($results['node_types'])) {
      $node_type = array_rand($results['node_types']);
    }
    else {
      $node_type = $results['node_types'];
    }

    if (!isset($results['messages'])) {
      $results['messages'] = [
        ['role' => 'system', 'content' => trim($system)],
        ['role' => 'user', 'content' => "Give me an example title for a/an $node_type page of content in less than 200 characters."]
      ];
    } else {
      $results['messages'][] = ['role' => 'user', 'content' => "Give me completely different title for a/an $node_type page of content in less than 200 characters."];
    }

    $uid = $users[array_rand($users)];

    // Add the content type label if required.
    $title_prefix = $results['add_type_label'] ? $this->nodeTypeStorage->load($node_type)->label() . ' - ' : '';

    $response = $this->client->chat()->create(
      [
        'model' => $model,
        'messages' => $results['messages'],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
      ],
    );

    $result = $response->toArray();

    // Remove any double quoting GPT might return.
    $title = str_replace('"', '', $result["choices"][0]["message"]["content"]);

    $results['messages'][] = ['role' => 'assistant', 'content' => trim($result["choices"][0]["message"]["content"])];

    $values = [
      'nid' => NULL,
      'type' => $node_type,
      'title' => $title_prefix . $title,
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'moderation_state' => 'published',
      'status' => TRUE,
      'promote' => mt_rand(0, 1),
      'created' => $this->time->getRequestTime() - mt_rand(0, $results['time_range']),
    ];

    if (isset($results['add_language'])) {
      $values['langcode'] = $this->getLangcode($results['add_language']);
    }

    $node = $this->nodeStorage->create($values);

    // A flag to let hook_node_insert() implementations know that this is a
    // generated node.
    $node->devel_generate = $results;

    // Populate non-skipped fields with sample values.
    $this->populateGptFields($node, $results, $title);

    // Remove the fields which are intended to have no value.
    foreach ($results['skip_fields'] as $field) {
      unset($node->$field);
    }

    // See devel_generate_entity_insert() for actions that happen before and
    // after this save.
    $node->save();

    // Add url alias if required.
    if (!empty($results['add_alias'])) {
      $path_alias = $this->aliasStorage->create([
        'path' => '/node/' . $node->id(),
        'alias' => '/node-' . $node->id() . '-' . $node->bundle(),
        'langcode' => $values['langcode'] ?? LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $path_alias->save();
    }

    // Add translations.
    if (isset($results['translate_language']) && !empty($results['translate_language'])) {
      $this->develGenerateContentAddNodeTranslation($results, $node);
    }
  }

  /**
   * Populate the fields on a given entity with sample values.
   *
   * This is not the same as the parent method populateFields because we need to communicate
   * options across to the client from user defined parameters.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be enriched with sample field values.
   * @param array $results
   *   Results information.
   * @param string $title
   *   The title of the node being generated by GPT.
   */
  public static function populateGptFields(EntityInterface $entity, array &$results, string $title) {
    if (!$entity->getEntityType()->entityClassImplements(FieldableEntityInterface::class)) {
      // Nothing to do.
      return;
    }

    $client = \Drupal::service('openai.client');

    $valid_gpt_field_types = [
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary',
    ];

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $instances */
    $instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    $instances = array_diff_key($instances, array_flip($results['skip_fields']));

    foreach ($instances as $instance) {
      $field_storage = $instance->getFieldStorageDefinition();
      $field_name = $field_storage->getName();
      $field_type = $field_storage->getType();
      if ($field_storage->isBaseField() && !in_array($field_name, $results['base_fields'])) {
        // Skip base field unless specifically requested.
        continue;
      }
      $max = $cardinality = $field_storage->getCardinality();
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // Just an arbitrary number for 'unlimited'.
        $max = rand(1, 3);
      }

      if (!in_array($field_type, $valid_gpt_field_types) || !in_array($field_name, $results['base_fields'])) {
        $entity->$field_name->generateSampleItems($max);
      } else {
        $values = [];

        if (in_array($field_type, ['text_long', 'text_with_summary']) && $results['html']) {
          $ask = "Provide content for a page of titled \"$title\" in basic HTML markup.";
        } else {
          $ask = "Provide content for a page of titled \"$title\".";
        }

        $ask .= "Give me appropriate content for this page and do not repeat yourself from previous answers. Do not include the page title \"$title\" in the response.";

        $results['messages'][] = ['role' => 'user', 'content' => $ask];

        $response = $client->chat()->create(
          [
            'model' => $results['model'],
            'messages' => $results['messages'],
            'temperature' => (float) $results['temperature'],
            'max_tokens' => (int) $results['max_tokens'],
          ],
        );

        $result = $response->toArray();

        $text = $result["choices"][0]["message"]["content"];

        if ($results['html']) {
          // @todo: any way of getting the list from the filter assigned to this entity/field?
          $text = Xss::filter($text, ['p', 'h2', 'h3', 'h4', 'h5', 'h6', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li']);
        }

        $text = trim($text);
        $results['messages'][] = ['role' => 'assistant', 'content' => $text];
        $values[] = $text;

        $entity->$field_name->setValue($values);
      }
    }
  }

}
