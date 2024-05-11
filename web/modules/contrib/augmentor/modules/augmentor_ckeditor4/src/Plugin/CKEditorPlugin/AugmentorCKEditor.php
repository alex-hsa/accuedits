<?php

namespace Drupal\augmentor_ckeditor4\Plugin\CKEditorPlugin;

use Drupal\augmentor\AugmentorManager;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "augmentor_ckeditor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "augmentor_ckeditor",
 *   label = @Translation("Augmentor"),
 *   module = "augmentor"
 * )
 */
class AugmentorCKEditor extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * The augmentor manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * Constructs an AugmentorCKEditor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AugmentorManager $augmentor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->augmentorManager = $augmentor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.augmentor.augmentors')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {}

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getModulePath('augmentor_ckeditor4') . '/js/plugins/augmentor_ckeditor/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['augmentor_ckeditor4/augmentor_ckeditor'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $url = Url::fromRoute(
      'augmentor.augmentor_execute',
      [],
      ['absolute' => TRUE],
    )->toString();

    return [
      'augmentor_ckeditor' => $this->buildOptions($editor),
      'augmentor_url' => $url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'augmentor_ckeditor' => [
        'id' => 'augmentor_ckeditor',
        'label' => $this->t('Augmentors'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" role="button" aria-label="{{ augmentor_text }}"><span class="ckeditor-button-dropdown">{{ augmentor_text }}<span class="ckeditor-button-arrow"></span></span></a>',
          '#context' => [
            'augmentor_text' => $this->t('Augmentors'),
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['description'] = [
      '#markup' => $this->t('Select the augmentors to be available in the editor.'),
    ];

    foreach ($this->augmentorManager->getAugmentors() as $uuid => $augmentor) {
      $form[$uuid] = [
        '#type' => 'checkbox',
        '#title' => $augmentor['label'],
        '#default_value' => $settings['plugins']['augmentor_ckeditor'][$uuid] ?? FALSE,
      ];
    }

    return $form;
  }

  /**
   * Builds the "augmentor" configuration part of the CKEditor JS settings.
   *
   * @see getConfig()
   *
   * @return array
   *   An array containing the "augmentor" configuration.
   */
  protected function buildOptions($editor) {
    $settings = $editor->getSettings();
    $augmentor_list = [];

    foreach ($this->augmentorManager->getAugmentors() as $uuid => $augmentor) {
      if (is_array($settings['plugins']['augmentor_ckeditor']) && array_key_exists($uuid, $settings['plugins']['augmentor_ckeditor']) && $settings['plugins']['augmentor_ckeditor'][$uuid]) {
        $augmentor_list[] = [
          'label' => $augmentor['label'],
          'value' => $uuid,
        ];
      }
    }

    return $augmentor_list;
  }

}
