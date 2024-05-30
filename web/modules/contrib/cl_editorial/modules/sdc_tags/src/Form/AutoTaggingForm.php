<?php

declare(strict_types=1);

namespace Drupal\sdc_tags\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\sdc_tags\ComponentTagDefault;
use Drupal\sdc_tags\ComponentTagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AutoTaggingForm extends FormBase {

  /**
   * The component tag manager.
   *
   * @var \Drupal\sdc_tags\ComponentTagPluginManager
   */
  private ComponentTagPluginManager $componentTagManager;

  /**
   * Creates a new form object.
   *
   * @param \Drupal\sdc_tags\ComponentTagPluginManager $component_tag_manager
   *   The tag manager.
   */
  public function __construct(ComponentTagPluginManager $component_tag_manager) {
    $this->componentTagManager = $component_tag_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.sdc_tags.component_tag'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sdc_tags_auto_tagging';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $tag = '') {
    $configuration = $this->config('sdc_tags.settings')->get("component_tags.$tag") ?? [];
    try {
      $tag_plugin = $this->componentTagManager->createInstance($tag, $configuration);
    }
    catch (PluginException $e) {
      throw new NotFoundHttpException();
    }
    if (!$tag_plugin instanceof ComponentTagDefault) {
      throw new NotFoundHttpException();
    }

    $form['#parents'] = [];
    $form['tag_id'] = [
      '#type' => 'value',
      '#value' => $tag,
    ];
    $form['data'] = ['#parents' => ['data']];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $tag_plugin->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('sdc_tags.component_tagging'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tag_plugin = $this->componentTagManager()->createInstance($form_state->getValue('tag_id'));
    if ($tag_plugin instanceof PluginFormInterface) {
      $tag_plugin->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    $tag_plugin = $this->componentTagManager()->createInstance($form_state->getValue('tag_id'));
    // The configuration is stored in the 'data' key in the form, pass that
    // through for submission.
    $tag_plugin->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));

    $plugin_settings = $tag_plugin->getConfiguration();
    $this->configFactory()->getEditable('sdc_tags.settings')->set(
      'component_tags.' . $plugin_settings['tag_id'],
      $plugin_settings
    )->save();
    $this->messenger()->addStatus($this->t('The auto-tagging configuration was successfully saved.'));
    $form_state->setRedirectUrl(Url::fromRoute('sdc_tags.component_tagging'));
  }


  private function componentTagManager(): ComponentTagPluginManager {
    if (!isset($this->componentTagManager)) {
      $this->componentTagManager = \Drupal::service('plugin.manager.sdc_tags.component_tag');
    }
    return $this->componentTagManager;
  }
}
