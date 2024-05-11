<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai\Utility\StringHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a search interface for testing vector search results.
 */
class SearchForm extends FormBase {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $openAiClient;

  /**
   * The vector client plugin manager.
   *
   * @var \Drupal\openai_embeddings\VectorClientPluginManager
   */
  protected $pluginManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_embeddings_search';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->openAiClient = $container->get('openai.client');
    $instance->pluginManager = $container->get('plugin.manager.vector_client');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search_input'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Search text'),
      '#description' => $this->t('Enter the text here to search for in Pinecone. When submitted, OpenAI will generate an embed and then find comparable content in Pinecone. Please note that each query counts against your API usage for OpenAI and Pinecone, and the maximum length allowed is 1024 characters (for this test interface). Based on the complexity of your input, OpenAI traffic, and other factors, a response can sometimes take up to 10-15 seconds to complete. Please allow the operation to finish.'),
      '#required' => TRUE,
      '#maxlength' => 1024,
    ];

    $form['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace/Collection'),
      '#description' => $this->t('Enter the namespace to search through. You can find the namespaces on the Stats tab. Leave blank if you are on the Starter plan.'),
      '#maxlength' => 64,
    ];

    $form['filter_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by'),
      '#options' => [
        'node' => $this->t('Nodes'),
        'taxonomy_term' => $this->t('Taxonomy terms'),
        'media' => $this->t('Media'),
        'paragraph' => $this->t('Paragraphs'),
      ],
      '#description' => $this->t('Select an entity type to filter by.'),
    ];

    $form['score_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Relevancy threshold'),
      '#min' => 0.1,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => 0.8,
      '#description' => $this->t('Set the relevancy threshold. Generally, a value of .8 or higher is considered to be most relevant.'),
    ];

    $form['response_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The search response from Pinecone will appear below.'),
    ];

    $form['response'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="openai-response">',
      '#suffix' => '</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-response',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getResponse(array &$form, FormStateInterface $form_state) {
    $query = $form_state->getValue('search_input');
    $filter_by = $form_state->getValue('filter_by');
    $score_threshold = $form_state->getValue('score_threshold');
    $namespace = $form_state->getValue('namespace');
    $text = StringHelper::prepareText($query, [], 1024);

    $response = $this->openAiClient->embeddings()->create([
      'model' => 'text-embedding-ada-002',
      'input' => $text,
    ]);

    $result = $response->toArray();

    $plugin_id = $this->configFactory()->get('openai_embeddings.settings')->get('vector_client_plugin');
    $vector_client = $this->pluginManager->createInstance($plugin_id);
    $query = $vector_client->query([
      'vector' => $result['data'][0]['embedding'],
      'top_k' => 8,
      'collection' => $namespace,
      'filter' => ['entity_type' => $filter_by],
    ]);
    $result = Json::decode($query->getBody()->getContents());

    // Milvus.
    if (isset($result['data'])) {
      $output = '<ul>';
      foreach ($result['data'] as $match) {
        $output .= '<li>' . $match['id'] . ' had a distance of ' . $match['distance'] . '</li>';
      }
      $output .= '</ul>';
    }

    // Pinecone.
    if (isset($result['matches'])) {
      $tracked = [];
      $output = '<ul>';
      foreach ($result['matches'] as $match) {
        if (isset($tracked[$match['metadata']['entity_type']]) && in_array($match['metadata']['entity_id'], $tracked[$match['metadata']['entity_type']])) {
          continue;
        }

        if ($match['score'] < $score_threshold) {
          continue;
        }

        $entity = $this->entityTypeManager
          ->getStorage($match['metadata']['entity_type'])
          ->load($match['metadata']['entity_id']);
        if ($entity instanceof EntityInterface) {
          $output .= '<li>' . $entity->toLink()->toString() . ' had a score of ' . $match['score'] . '</li>';
        }

        $tracked[$match['metadata']['entity_type']][] = $match['metadata']['entity_id'];
      }
      $output .= '</ul>';

      if (empty($tracked)) {
        $output = '<p>No results were found, or results were excluded because they did not meet the relevancy score threshold.</p>';
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#openai-response', $output));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
