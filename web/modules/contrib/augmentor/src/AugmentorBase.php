<?php

namespace Drupal\augmentor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileRepositoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for augmentors.
 *
 * @see \Drupal\augmentor\Annotation\Augmentor
 * @see \Drupal\augmentor\AugmentorInterface
 * @see \Drupal\augmentor\AugmentorManager
 * @see plugin_api
 */
abstract class AugmentorBase extends PluginBase implements AugmentorInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The augmentor label.
   *
   * @var string
   */
  protected $label;

  /**
   * The augmentor ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the augmentor.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * The API key of the augmentor.
   *
   * @var string
   */
  protected $key = '';

  /**
   * The debug flag.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerInterface $logger,
    KeyRepositoryInterface $key_repository,
    AccountInterface $current_user,
    FileSystemInterface $file_system,
    FileRepositoryInterface $file_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->logger = $logger;
    $this->keyRepository = $key_repository;
    $this->currentUser = $current_user;
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('augmentor'),
      $container->get('key.repository'),
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('file.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => '',
      '#augmentor' => [
        'id' => $this->pluginDefinition['id'],
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['label'],
    ];

    $form['key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('API key'),
      '#default_value' => $this->configuration['key'],
      '#empty_value' => '',
      '#empty_label' => '',
    ];

    $form['debug'] = [
      '#title' => $this->t('Enable debugging'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['debug'],
    ];

    return $form;
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
    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['key'] = $form_state->getValue('key');
    $this->configuration['debug'] = $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setKey($key) {
    $this->key = $key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyObject() {
    if (empty($this->key)) {
      return;
    }
    return $this->keyRepository->getKey($this->key);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue() {
    if (empty($this->key)) {
      return;
    }
    return $this->getKeyObject()->getKeyValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getDebug() {
    return $this->debug;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'label' => $this->label(),
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
      'key' => $this->getKey(),
      'debug' => $this->getDebug(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'label' => '',
      'uuid' => '',
      'weight' => '',
      'key' => '',
      'debug' => FALSE,
      'settings' => [],
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->label = $this->configuration['label'];
    $this->key = $this->configuration['key'];
    $this->debug = $this->configuration['debug'];
    $this->uuid = $configuration['uuid'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'key' => '',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Removes superfluous whitespace and unescapes HTML entities.
   *
   * @param string $value
   *   The text to process.
   *
   * @return string
   *   The text without unnecessary whitespace and HTML entities transformed
   *   back to plain text.
   */
  protected function normalizeText($value) {
    $value = Html::decodeEntities($value);
    $value = trim($value);
    return $value;
  }

  /**
   * Dumps a message to the logger.
   *
   * @param array $messages
   *   The messages to process.
   */
  public function debug(array $messages) {
    if ($this->getDebug()) {
      $this->logger->debug(json_encode($messages, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
    }
  }

}
