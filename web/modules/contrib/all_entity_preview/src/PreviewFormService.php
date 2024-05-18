<?php

namespace Drupal\preview;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\preview\Access\EntityPreviewAccessCheck;
use Drupal\preview\Form\PreviewSettingsForm;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains entity form alteration logic.
 */
class PreviewFormService {

  use StringTranslationTrait;

  public const TEMPSTORE_NAME = 'entity_preview';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The access checker.
   *
   * @var \Drupal\preview\Access\EntityPreviewAccessCheck
   */
  protected $accessChecker;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Service object constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \Drupal\preview\Access\EntityPreviewAccessCheck $access_checker
   *   The access checker.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityPreviewAccessCheck $access_checker,
    AccountInterface $current_user,
    RequestStack $request_stack,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->configFactory = $config_factory;
    $this->accessChecker = $access_checker;
    $this->currentUser = $current_user;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * Form alter method.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, string $form_id) {
    $formObject = $form_state->getFormObject();
    if (!($formObject instanceof ContentEntityFormInterface)) {
      return;
    }

    $entity = $formObject->getEntity();
    $settings = $this->configFactory->get(PreviewSettingsForm::SETTINGS)->getRawData();
    if (empty($settings[$entity->getEntityTypeId()][$entity->bundle()])) {
      return;
    }

    if (!$this->accessChecker->access($this->currentUser, $entity)->isAllowed()) {
      return;
    }

    $store = $this->tempStoreFactory->get(static::TEMPSTORE_NAME);

    // Attempt to load from preview when the uuid is present unless we are
    // rebuilding the form.
    $request_uuid = $this->currentRequest->query->get('uuid');
    if (!$form_state->isRebuilding() && $request_uuid && $preview = $store->get($request_uuid)) {
      /** @var \Drupal\Core\Form\FormStateInterface $preview */
      $input = $preview->getUserInput();
      foreach ($form_state->getCleanValueKeys() as $key) {
        unset($input[$key]);
      }

      $form_state->setStorage($preview->getStorage());
      $form_state->setUserInput($preview->getUserInput());

      // Rebuild the form.
      $form_state->setRebuild();

      // The combination of having user input and rebuilding the form means
      // that it will attempt to cache the form state which will fail if it is
      // a GET request.
      $form_state->setRequestMethod('POST');

      $this->entity = $preview->getFormObject()->getEntity();
      $this->entity->in_preview = NULL;

      $form_state->set('has_been_previewed', TRUE);

      // Rebuild the form to apply new values.
      $form = $formObject->buildForm($form, $form_state);
    }

    // Add preview button.
    $form['actions']['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#weight' => 20,
      '#submit' => ['::submitForm', [get_class($this), 'preview']],
    ];

    // Add cleanup action on save.
    $form['actions']['submit']['#submit'][] = [get_class($this), 'cleanup'];
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function preview(array $form, FormStateInterface $form_state) {
    $store = \Drupal::service('tempstore.private')->get(static::TEMPSTORE_NAME);
    $entity = $form_state->getFormObject()->getEntity();
    $entity->in_preview = TRUE;
    $store->set($entity->uuid(), $form_state);

    $settings = \Drupal::config(PreviewSettingsForm::SETTINGS)->getRawData();
    $route_parameters = [
      'entity_preview' => $entity->uuid(),
      'view_mode_id' => $settings[$entity->getEntityTypeId()][$entity->bundle()],
    ];

    $options = [];
    $query = \Drupal::request()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('preview.entity_preview', $route_parameters, $options);
  }

  /**
   * Perform tempstore cleanup.
   */
  public static function cleanup(array $form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $store = \Drupal::service('tempstore.private')->get(static::TEMPSTORE_NAME);
    $store->delete($entity->uuid());
  }

}
