<?php

namespace Drupal\smartmenus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Utility\Html;
use Drupal\smartmenus\SmartmenusUtil;

/**
 * Provides a 'SmartMenusBlock' block.
 *
 * @Block(
 *  id = "smart_menus_block",
 *  admin_label = @Translation("Smart menus block"),
 *  category = @Translation("smartmenus")
 * )
 */
class SmartMenusBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Menu\MenuLinkTreeInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;


  /**
   * Drupal\Component\Utility\Html definition.
   *
   * @var \Drupal\Component\Utility\Html
   */
  protected $htmlUtil;

  /**
   * Drupal\smartmenus\SmartmenusUtil definition.
   * @var SmartmenusUtil
   */
  protected $smartmenusUtil;

  /**
   * SmartMenusBlock constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param MenuLinkTreeInterface $menu_link_tree
   * @param Renderer $renderer
   * @param SmartmenusUtil $smartmenusUtil
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MenuLinkTreeInterface $menu_link_tree,
    Renderer $renderer,
    SmartmenusUtil $smartmenusUtil
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
    $this->renderer = $renderer;
    $this->smartmenusUtil = $smartmenusUtil;
    // hard coding this
    $this->htmlUtil = new Html();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('renderer'),
      $container->get('smartmenus.util')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['smartmenus'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Smartmenus Settings'),
    );


    $form['smartmenus']['smartmenus_menu'] = array(
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => menu_ui_get_menus(),
      '#description' => t('The desired menu to render as a Smartmenu.'),
      '#default_value' => $config['smartmenus_menu'] ? $config['smartmenus_menu'] : '',
      '#required' => TRUE
    );

    $form['smartmenus']['smartmenus_toggle'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display a menu toggle button on small screens'),
      '#default_value' => $config['smartmenus_toggle'] ? $config['smartmenus_toggle'] : '',
    );

    $form['smartmenus']['smartmenus_orient'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Orientation'),
      '#options' => array(
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ),
      '#default_value' => $config['smartmenus_orient'] ? $config['smartmenus_orient'] : 'horizontal',
    );

    $form['smartmenus']['smartmenus_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Smart menus theme'),
      '#options' => $this->getThemeOptions(),
      '#default_value' => $config['smartmenus_theme'] ? $config['smartmenus_theme'] : $this->getDefaultTheme(),
      '#required' => true
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['smartmenus_menu'] = $values['smartmenus']['smartmenus_menu'];
    $this->configuration['smartmenus_toggle'] = $values['smartmenus']['smartmenus_toggle'];
    $this->configuration['smartmenus_orient'] = $values['smartmenus']['smartmenus_orient'];
    $this->configuration['smartmenus_theme'] = $values['smartmenus']['smartmenus_theme'];
  }

  /**
   * @return array
   */
  public function getThemeOptions() {
    return $this->smartmenusUtil->getAvailableMenuThemesList();
  }

  /*
   * @return string
   * Returns the currently selected theme for the block
   */
  public function getSelectedTheme() {
    return ($this->getConfiguration())['smartmenus_theme'];
  }

  /**
   * @return string
   * Return the selected menu_name for the block
   */
  public function getSelectedMenu() {
    return ($this->getConfiguration())['smartmenus_menu'];
  }

  /**
   * @return string
   * Return the orientation of the menu block.
   */
  public function getSelectedOrientation() {
    return ($this->getConfiguration())['smartmenus_orient'];
  }

  /**
   * @return int
   * Returns 0,1 depending on the values of the checkbox.
   */
  public function isToggleEnabled() {
    return ($this->getConfiguration())['smartmenus_toggle'];
  }

  /**
   * @return string
   * Get default theme set using module config form.
   */
  public function getDefaultTheme() {
    $config = \Drupal::config($this->smartmenusUtil->getConfigFormSettingsName());
    $theme = $config->get('smartmenus_theme');
    return isset($theme) ?  $theme : '';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getSelectedMenu();
    // Get the menu parameters for the current route, but remove the expanded
    // parents, so that we get the entire tree.
    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->expandedParents = [];
    // Get the selected menu.
    $tree = $this->menuLinkTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    // Run transform to check the access and sort the menu items
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree = $this->menuLinkTree->build($tree);

    $build['#theme'] = 'smartmenus_block';
    $build['#attached']['library'] = ['smartmenus/smartmenus-custom'];

    $menuOrientation = 'sm-' . $this->getSelectedOrientation();

    $toggle = null;
    if ($this->isToggleEnabled()) {
      $render_toggle = [
        '#theme' => 'smartmenus_toggle'
      ];

      $toggle = $this->renderer->renderPlain($render_toggle);
    }

    $rendered_menu = [
      '#theme' => 'smartmenus_menu',
      '#items' => $tree['#items'],
      '#attributes' => [
        'class' => [$this->getSelectedTheme(), $menuOrientation]
      ],
      '#menu_name' => $menu_name,
      '#toggle' => $toggle,
    ];

    $build['#menu_tree'] = $this->renderer->renderPlain($rendered_menu);
    $build['#cache'] = [
      'contexts' => Cache::mergeContexts(['url.path'], $rendered_menu['#cache']['contexts'] ?? []),
      'tags' => Cache::mergeTags($tree['#cache']['tags'], $rendered_menu['#cache']['tags'] ?? []),
    ];

    return $build;
  }
}
