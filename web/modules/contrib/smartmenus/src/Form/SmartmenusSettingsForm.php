<?php

namespace Drupal\smartmenus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smartmenus\SmartmenusUtil;

/**
 * Class SmartmenusSettingsForm.
 */
class SmartmenusSettingsForm extends ConfigFormBase {

  /**
   * @var SmartmenusUtil
   */
  protected $smartmenusUtil;

  /**
   * Class constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory, SmartmenusUtil $smartmenusUtil)
  {
    parent::__construct($config_factory);
    $this->smartmenusUtil = $smartmenusUtil;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('smartmenus.util')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      $this->smartmenusUtil->getConfigFormSettingsName(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartmenus_settings_form';
  }


  /**
   * @return array
   */
  public function getThemeOptions() {
    return $this->smartmenusUtil->getAvailableMenuThemesList();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->smartmenusUtil->getConfigFormSettingsName());
    $form['container'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Smartmenus Settings'),
    );

    $form['container']['smartmenus_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Smart menus theme'),
      '#options' => $this->getThemeOptions(),
      '#default_value' => $config->get('smartmenus_theme'),
      '#required' => true
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config($this->smartmenusUtil->getConfigFormSettingsName())
      ->set('smartmenus_theme', $form_state->getValue('smartmenus_theme'))
      ->save();
  }

}
