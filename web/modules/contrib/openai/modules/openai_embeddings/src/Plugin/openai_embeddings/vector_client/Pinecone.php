<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings\Plugin\openai_embeddings\vector_client;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai_embeddings\VectorClientPluginBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Pinecone vector client plugin.
 *
 * @VectorClient(
 *   id = "pinecone",
 *   label = "Pinecone",
 *   description = "Client implementation to connect and use the Pinecone API.",
 * )
 */
class Pinecone extends VectorClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hostname' => '',
      'api_key' => '',
      'disable_namespace' => 0,
    ];
  }

  /**
   * Get the pinecone client.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  public function getClient(): Client {
    if (!isset($this->httpClient)) {
      $options = [
        'headers' => [
          'Content-Type' => 'application/json',
          'API-Key' => $this->getConfiguration()['api_key'],
        ],
        'base_uri' => $this->getConfiguration()['hostname'],
      ];
      $this->httpClient = $this->http_client_factory->fromOptions($options);
    }
    return $this->httpClient;
  }

  /**
   * Submits a query to the API service.
   *
   * @param array $parameters
   *   An array with at least key 'vector'. The key 'collection'
   *   is required if not using free Pinecone starter.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function query(array $parameters): ResponseInterface {
    if (empty($parameters['vector'])) {
      throw new \Exception('Vector to query is required by Pinecone');
    }
    $payload = [
      'vector' => $parameters['vector'] ?? FALSE,
      'topK' => $parameters['top_k'] ?? 5,
      'includeMetadata' => $parameters['include_metadata'] ?? TRUE,
      'includeValues' => $parameters['include_values'] ?? FALSE,
    ];

    // We use 'collection' as that appears to be the more common naming
    // across vector databases and that allows a consistent queue
    // worker that does not care about the plugin used. For the pinecone
    // query itself it expects 'namespace'.
    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    if (!empty($filters)) {
      $payload['filter'] = $filters;
    }

    return $this->getClient()->post('/query', [
      'json' => $payload,
    ]);
  }

  /**
   * Inserts or updates an array of vectors in Pinecone.
   *
   * @param array $parameters
   *   An array with at least key 'vectors'. The key 'collection'
   *   is required if not using free Pinecone starter.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function upsert(array $parameters): ResponseInterface {
    if (empty($parameters['vectors'])) {
      throw new \Exception('Vectors to insert or update are required by Pinecone');
    }
    $payload = [
      'vectors' => $parameters['vectors'],
    ];

    // See description in ::query().
    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    return $this->getClient()->post('/vectors/upsert', [
      'json' => $payload,
    ]);
  }

  /**
   * Look up and returns vectors, by ID, from a single namespace.
   *
   * @param array $parameters
   *   An array with at least key 'source_ids'. The key
   *   'collection' is required if not using free Pinecone starter.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetch(array $parameters): ResponseInterface {
    if (empty($parameters['source_ids'])) {
      throw new \Exception('Source IDs to retrieve are required by Milvus');
    }
    $payload = [
      'ids' => $parameters['source_ids'],
    ];

    // See description in ::query().
    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    return $this->getClient()->get('/vectors/fetch', [
      'query' => $payload,
    ]);
  }

  /**
   * Delete records in Pinecone.
   *
   * @param array $parameters
   *   An array with at least key 'source_ids'. The key
   *   'collection' is required if not using free Pinecone starter.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function delete(array $parameters): ResponseInterface {
    if (empty($parameters['source_ids']) && empty($parameters['deleteAll'])) {
      throw new \Exception('Either "source_ids" to delete or "deleteAll" is required by Pinecone');
    }
    if (!empty($parameters['deleteAll'])) {
      throw new \Exception('Please use the "deleteAll" method.');
    }
    $payload = [];

    if (!empty($parameters['source_ids'])) {
      $payload = [
        'ids' => $parameters['source_ids'],
      ];
    }

    // If filter is provided, ensure that it is not free
    // Pinecone.
    if (!$payload['deleteAll'] && $this->getConfiguration()['disable_namespace']) {
      throw new \Exception('Pinecone free starter plan does not support filters on deletion.');
    }
    elseif ($payload['deleteAll'] && $this->getConfiguration()['disable_namespace']) {
      $this->messenger->addWarning('Pinecone free starter plan does not support Delete All. Please visit the Pinecone website to manually clear the index.');
      throw new \Exception('Pinecone free starter plan does not support filters on deletion.');
    }

    // See description in ::query().
    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    if (!empty($parameters['filter'])) {
      $payload['filter'] = $parameters['filter'];
    }

    return $this->getClient()->post('/vectors/delete', [
      'json' => $payload,
    ]);
  }

  /**
   * Delete all records in Pinecone.
   *
   * @param array $parameters
   *   An array with at least key 'collection'. This method
   *   is not allowed within the free Starter Pinecone. Full
   *   deletion for the free Starter must be done via
   *   Pinecone's website.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAll(array $parameters): void {

    // If filter is provided, ensure that it is not free
    // Pinecone.
    if ($this->getConfiguration()['disable_namespace']) {
      $this->messenger->addWarning('Pinecone free starter plan does not support Delete All. Please visit the Pinecone website to manually clear the index.');
      throw new \Exception('Pinecone free starter plan does not support filters on deletion.');
    }
    if (empty($parameters['collection'])) {
      throw new \Exception('Namespace is required for delete all within Pinecone. Please add the "collection" parameter.');
    }

    $payload = [
      'deleteAll' => TRUE,
      'namespace' => $parameters['collection'],
    ];

    $this->getClient()->post('/vectors/delete', [
      'json' => $payload,
    ]);
  }

  /**
   * Returns statistics about the index's contents.
   */
  public function stats(): array {
    return $this->buildStatsTable();
  }

  /**
   * Build a table with statistics specific to Pinecone.
   *
   * @return array
   *   The stats table render array.
   */
  public function buildStatsTable(): array {
    $rows = [];

    $header = [
      [
        'data' => $this->t('Namespaces'),
      ],
      [
        'data' => $this->t('Vector Count'),
      ],
    ];

    try {
      $stats = $this->getClient()->post(
        '/describe_index_stats',
      );
      $response = Json::decode($stats->getBody()->getContents());

      foreach ($response['namespaces'] as $key => $namespace) {
        if (!mb_strlen($key)) {
          $label = $this->t('No namespace entered');
        }
        else {
          $label = $key;
        }

        $rows[] = [
          $label,
          $namespace['vectorCount'],
        ];
      }
    }
    catch (RequestException | \Exception $e) {
      $this->logger->error('An exception occurred when trying to view index stats. It is likely either configuration is missing or a network error occurred.');
    }

    $build['stats'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No statistics are available.'),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->getConfiguration()['api_key'],
      '#description' => $this->t('The API key is required to make calls to Pinecone for vector searching.'),
    ];

    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $this->getConfiguration()['hostname'],
      '#description' => $this->t('The hostname or base URI where your Pinecone instance is located.'),
    ];

    $form['disable_namespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable namespace'),
      '#default_value' => $this->getConfiguration()['disable_namespace'],
      '#description' => $this->t('The starter plan does not support namespaces. This means that all items get indexed together by disabling this; however, it allows you to at least demo the features.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->getClient()->post(
        '/describe_index_stats',
      );
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('api_key', $exception->getMessage());
      $form_state->setErrorByName('hostname', $exception->getMessage());
    }
  }

}
