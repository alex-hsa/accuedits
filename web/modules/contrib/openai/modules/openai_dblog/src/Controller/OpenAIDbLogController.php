<?php

declare(strict_types=1);

namespace Drupal\openai_dblog\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\dblog\Controller\DbLogController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the log details page to provide OpenAI powered explanations.
 */
class OpenAIDbLogController extends DbLogController {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->api = $container->get('openai.api');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function eventDetails($event_id) {
    $build = parent::eventDetails($event_id);
    $rows = $build['dblog_table']['#rows'];
    $severity = trim(strip_tags($rows[6][1]->render()));
    $config = $this->config('openai_dblog.settings');
    $levels = array_filter($config->get('levels'));
    $model = $config->get('model');

    if (!array_key_exists($severity, $levels)) {
      return $build;
    }

    $header = $this->t('Explanation (powered by <a href=":link">OpenAI</a>)', [':link' => 'https://openai.com']);
    $message = Unicode::truncate(trim(strip_tags($rows[5][1]->render())), 256, TRUE);
    $hash = $this->generateHash($message);
    $exists = $this->hashSearch($hash);

    if ($exists) {
      $rows[] = [
        [
          'data' => $header,
          'header' => TRUE,
        ],
        [
          'data' => [
            '#markup' => nl2br($exists["explanation"]),
          ],
        ],
      ];
    }
    else {
      try {
        if (str_contains($model, 'gpt')) {
          $messages = [
            ['role' => 'system', 'content' => 'You are a PHP, Drupal 9 and Drupal 10 expert programmer. Please return all answers without using first, second, or third person voice.'],
            ['role' => 'user', 'content' => 'What does this error mean on my Drupal site and how can I fix it? The error is: "' . $message . '"']
          ];

          $result = $this->api->chat($model, $messages, 0.4, 3900);
        } else {
          $prompt = 'What does this error mean on my Drupal site and how can I fix it? The error is: "' . $message . '"';
          $result = $this->api->completions($model, $prompt, 0.4, 2048);
        }

        $this->insertExplanation($hash, $message, $result);
      }
      catch (\Exception $e) {
        $this->getLogger('openai_dblog')->error('Error when trying to obtain a response from OpenAI.');
      }

      $rows[] = [
        [
          'data' => $header,
          'header' => TRUE,
        ],
        [
          'data' => [
            '#markup' => isset($result) ? nl2br($result) : 'No possible explanations were found, or the API service is not responding.',
          ],
        ],
      ];
    }

    $build['dblog_table']['#rows'] = $rows;
    return $build;
  }

  /**
   * Generate a hash value from the error string.
   *
   * @param string $message
   *   The error string.
   *
   * @return string
   *   The hash value.
   */
  protected function generateHash(string $message): string {
    return hash('sha256', $message);
  }

  /**
   * Lookup a hash value for an existing response from OpenAI.
   *
   * @param string $hash
   *   The hashed error string.
   *
   * @return bool|array
   *   The database result, or FALSE if no result was found.
   */
  protected function hashSearch(string $hash): bool|array {
    return $this->database->query('SELECT explanation FROM {openai_dblog} WHERE hash = :hash LIMIT 1', [':hash' => $hash])->fetchAssoc();
  }

  /**
   * Inserts a record of the OpenAI explanation.
   *
   * @param string $hash
   *   The hashed error string.
   * @param string $message
   *   The original error message.
   * @param string $explanation
   *   The explanation returned from OpenAI.
   */
  protected function insertExplanation(string $hash, string $message, string $explanation): void {
    $this->database->insert('openai_dblog')
      ->fields([
        'hash' => $hash,
        'message' => $message,
        'explanation' => strip_tags(trim($explanation)),
      ])
      ->execute();
  }

}
