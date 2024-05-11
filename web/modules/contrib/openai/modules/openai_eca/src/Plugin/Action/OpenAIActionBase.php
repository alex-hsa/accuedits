<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *  Base class for OpenAI / ChatGPT related actions.
 */
abstract class OpenAIActionBase extends ConfigurableActionBase {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->api = $container->get('openai.api');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'token_input' => '',
        'token_result' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['token_input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token input'),
      '#default_value' => $this->configuration['token_input'],
      '#description' => $this->t('The data input for OpenAI.'),
      '#weight' => -10,
      '#eca_token_reference' => TRUE,
    ];

    $form['token_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token result'),
      '#default_value' => $this->configuration['token_result'],
      '#description' => $this->t('The response from OpenAI will be stored into the token result field to be used in future steps.'),
      '#weight' => -9,
      '#eca_token_reference' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_input'] = $form_state->getValue('token_input');
    $this->configuration['token_result'] = $form_state->getValue('token_result');
    parent::submitConfigurationForm($form, $form_state);
  }

}
