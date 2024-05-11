<?php

namespace Drupal\openai_embeddings;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginBase;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for vector clients.
 */
abstract class VectorClientPluginBase extends PluginBase implements VectorClientInterface {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The openai_embeddings.settings editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The openai embeddings logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected ClientFactory $http_client_factory,
    protected ConfigFactoryInterface $config_factory,
    protected LoggerInterface $logger,
    MessengerInterface $messenger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;

    // Get the configuration mapping within the openai_embeddings settings.
    // If none is found, set to the default configuration.
    $this->config = $config_factory->getEditable('openai_embeddings.settings') ?? [];
    $configuration = $this->config->get('vector_clients.' . $plugin_id) ?? [];
    $configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client_factory'),
      $container->get('config.factory'),
      $container->get('logger.factory')->get('openai_embeddings'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());

    // Save the configuration.
    $this->config->set(
      'vector_clients.' . $this->getPluginId(),
      $this->getConfiguration()
    )->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
