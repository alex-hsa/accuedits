<?php

namespace Drupal\openai_embeddings\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirm form to delete all vector database items.
 */
class DeleteConfirmForm extends ConfirmFormBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The vector client plugin manager.
   *
   * @var \Drupal\openai_embeddings\VectorClientPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->pluginManager = $container->get('plugin.manager.vector_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_embeddings_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete all items in your vector database index?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will delete all items in your vector database instance. Note that this action is NOT permitted if you are using Pinecone and on their Starter plan.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('openai_embeddings.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $results = $this->database->query('SELECT entity_type, field_name FROM {openai_embeddings}');
    $plugin_id = $this->configFactory()->get('openai_embeddings.settings')->get('vector_client_plugin');
    $vector_client = $this->pluginManager->createInstance($plugin_id);
    foreach ($results as $result) {
      $vector_client->deleteAll([
        'collection' => $result->entity_type,
      ]);
    }

    $this->messenger()->addStatus($this->t('All items have been deleted in the vector database.'));
    $form_state->setRedirect('openai_embeddings.stats');
  }

}
