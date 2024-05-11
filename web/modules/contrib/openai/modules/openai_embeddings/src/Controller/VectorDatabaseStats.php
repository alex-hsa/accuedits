<?php

namespace Drupal\openai_embeddings\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\openai_embeddings\VectorClientPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for OpenAI Embeddings routes.
 */
class VectorDatabaseStats extends ControllerBase {

  /**
   * The controller constructor.
   *
   * @param \Drupal\openai_embeddings\VectorClientPluginManager $pluginManager
   *   The vector client plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(
    protected VectorClientPluginManager $pluginManager,
    protected ConfigFactoryInterface $config
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.vector_client'),
      $container->get('config.factory')
    );
  }

  /**
   * Builds the response.
   */
  public function index() {
    $plugin_id = $this->config->get('openai_embeddings.settings')->get('vector_client_plugin');
    $vector_client = $this->pluginManager->createInstance($plugin_id);
    return $vector_client->stats();
  }

}
