<?php

namespace Drupal\content_templates\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Content template edit forms.
 *
 * @ingroup content_templates
 */
class ContentTemplateForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\content_templates\Entity\ContentTemplate $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $form_state->getFormObject()->getEntity();
    $form['actions']['submit']['#value'] = $entity->isNew() ? $this->t('Create template') : $this->t('Save template');
    $form['field_source']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label content template.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label content template.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('content_templates.node.template', ['node' => $entity->get('field_source')->target_id]);
  }

}
