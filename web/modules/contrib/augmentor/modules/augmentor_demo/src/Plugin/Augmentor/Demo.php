<?php

namespace Drupal\augmentor_demo\Plugin\Augmentor;

use Drupal\augmentor\AugmentorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Demo Augmentor plugin implementation.
 *
 * @Augmentor(
 *   id = "demo",
 *   label = @Translation("Demo Augmentor"),
 *   description = @Translation("Split text into sentences separated by a dot."),
 * )
 */
class Demo extends AugmentorBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'output' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['key']);

    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->configuration['output'],
      '#options' => [
        'content' => $this->t('Content'),
        'tags' => $this->t('Tags'),
      ],
      '#description' => $this->t('Output format to display the processed text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['output'] = $form_state->getValue('output');
  }

  /**
   * Split text into sentences separated by a dot.
   *
   * @param string $input
   *   Input text to be processed.
   *
   * @return array
   *   The output of the processing.
   */
  public function execute($input) {
    $input = str_replace([',', ';', ':', ',', "'"], '', strip_tags($input));
    $input_exploded_by_dot = explode('.', $input);
    $output = [];
    if ($this->configuration['output'] == 'content') {
      $output = [$input_exploded_by_dot[0]];
    }
    else {
      $input_exploded_by_space = explode(' ', $input_exploded_by_dot[0]);
      $output = array_unique($input_exploded_by_space);
    }

    return ['default' => $output];
  }

}
