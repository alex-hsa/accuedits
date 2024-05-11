<?php

namespace Drupal\augmentor\Form;

use Drupal\augmentor\AugmentorInterface;
use Drupal\augmentor\AugmentorManager;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for augmentor instances.
 */
abstract class AugmentorFormBase extends FormBase {

  /**
   * The augmentor manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * The augmentor instance.
   *
   * @var \Drupal\augmentor\AugmentorInterface
   */
  protected $augmentor;

  /**
   * The uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Constructs a new AugmentorFormBase.
   *
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The uuid generator.
   */
  public function __construct(AugmentorManager $augmentor_manager, UuidInterface $uuid_generator) {
    $this->augmentorManager = $augmentor_manager;
    $this->uuidGenerator = $uuid_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.augmentor.augmentors'),
      $container->get('uuid'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'augmentor_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $augmentor
   *   The augmentor ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $augmentor = NULL) {
    try {
      $this->augmentor = $this->prepareAugmentor($augmentor);

    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid augmentor id: '$augmentor'.");
    }
    $request = $this->getRequest();

    if (!($this->augmentor instanceof AugmentorInterface)) {
      throw new NotFoundHttpException();
    }

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $this->augmentor->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->augmentor->getPluginId(),
    ];

    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->augmentor->buildConfigurationForm($form['settings'], $subform_state);
    $form['settings']['#tree'] = TRUE;

    // Check URL for a weight,then augmentor,otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->augmentor->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('augmentor.list'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The augmentor configuration is stored in the 'settings' key in
    // the form, pass that through for validation.
    $this->augmentor->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The augmentor configuration is stored in the 'settings' key in
    // the form, pass that through for submission.
    $this->augmentor->submitConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
    $this->augmentor->setWeight($form_state->getValue('weight'));
    $config = $this->augmentorManager->getAugmentorConfig();
    $augmentors = $config->get('augmentors');
    $uuid = $form_state->getValue('uuid');
    $augmentor_configuration = $this->augmentor->getConfiguration();

    if (empty($uuid)) {
      $uuid = $this->uuidGenerator->generate();
    }

    $augmentors[$uuid] = [
      'label' => $form_state->getValue('settings')['label'],
      'weight' => $form_state->getValue('weight'),
      'debug' => $form_state->getValue('debug'),
      'type' => $augmentor_configuration['id'],
      'configuration' => $augmentor_configuration,
    ];

    $config->set('augmentors', $augmentors);
    $config->save();
    $this->messenger()->addStatus($this->t('The augmentor was successfully saved.'));
    $form_state->setRedirectUrl(Url::fromRoute('augmentor.list'));
  }

  /**
   * Converts an Augmentor ID into an object.
   *
   * @param string $augmentor
   *   Augmentor ID.
   *
   * @return \Drupal\augmentor\AugmentorInterface
   *   The augmentor object.
   */
  abstract protected function prepareAugmentor($augmentor);

}
