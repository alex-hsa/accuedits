<?php

namespace Drupal\require_revision_log_message\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for admin settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs an AutoParagraphForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'require_revision_log_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'require_revision_log_message.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('require_revision_log_message.adminsettings');

    $types = [];
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Content Types selected in this list will require revision log messages'),
      '#options' => $types,
      '#default_value' => $config->get('content_types', []),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $content_types = $form_state->getValue('content_types');
    $content_types = array_filter($content_types);

    $this->config('require_revision_log_message.adminsettings')
      ->set('content_types', $content_types)
      ->save();
  }

}
