<?php

namespace Drupal\sdc_tags;

use Drupal\cl_editorial\Form\ComponentFiltersFormTrait;
use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Default class used for component_tags plugins.
 */
class ComponentTagDefault extends PluginBase implements ComponentTagInterface, PluginFormInterface, ConfigurableInterface {

  use ComponentFiltersFormTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
        'tag_id' => $this->getPluginId(),
      ] + $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'statuses' => [
        ExtensionLifecycle::STABLE,
        ExtensionLifecycle::EXPERIMENTAL,
        ExtensionLifecycle::DEPRECATED,
        ExtensionLifecycle::OBSOLETE,
      ],
      'forbidden' => [],
      'allowed' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->buildSettingsForm(
      $form,
      $form_state,
      \Drupal::service(NoThemeComponentManager::class),
      $this->getConfiguration(),
      ['data'],
      $this->t(
        'Set the tagging rules for <strong>%name</strong> (<code>@id</code>). You can tag specific components, or tag all present and future components but a specific selection.',
        ['@id' => $this->getPluginId(), '%name' => $this->label()]
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    //    $form_state->setErrorByName('lorem', 'Error!');
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'statuses' => array_keys(array_filter($form_state->getValue(['filters', 'statuses']))),
      'forbidden' => array_keys(array_filter($form_state->getValue([
        'filters',
        'refine',
        'forbidden',
      ]))),
      'allowed' => array_keys(array_filter($form_state->getValue([
        'filters',
        'refine',
        'allowed',
      ]))),
    ]);
  }

}
