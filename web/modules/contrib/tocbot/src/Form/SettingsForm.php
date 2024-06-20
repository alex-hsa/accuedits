<?php

namespace Drupal\tocbot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tocbot\TocbotHelper;

/**
 * Configures tocbot settings for this site.
 *
 * @internal
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tocbot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tocbot.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all settings.
    $settings = $this->config('tocbot.settings');

    // Module settings section.
    $form['tocbot_module_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Module settings'),
      '#description' => $this->t('Module settings to customize tocbot.'),
      '#open' => TRUE,
    ];

    $form['tocbot_module_settings']['extra_body_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra Body Class on Activation'),
      '#default_value' => $settings->get('extra_body_class'),
      '#description' => $this->t('When the tocbot is activated you can add an extra class to the body to adjust the theme of your site if desired. Leave blank if not needed.'),
    ];
    $form['tocbot_module_settings']['min_activate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('minActivate (number)'),
      '#default_value' => $settings->get('min_activate'),
      '#description' => $this->t('Only activate if greater than or equal to this many headings'),
    ];
    $form['tocbot_module_settings']['create_auto_ids'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create Automatic Ids'),
      '#default_value' => $settings->get('create_auto_ids'),
      '#description' => $this->t('Using Javascript automatic Ids can be created needed for Tocbot to work, or disable it if you have another means of creating ids such as a text filter module'),
    ];

    // Javascript settings section.
    $form['tocbot_js_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Tocbot JS settings'),
      '#description' => $this->t('Tocbot API settings passed to javascript <a href="https://tscanlin.github.io/tocbot/#api">See API for details</a>.'),
      '#open' => TRUE,
    ];
    $form['tocbot_js_settings']['toc_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tocSelector'),
      '#default_value' => $settings->get('toc_selector'),
      '#description' => $this->t('Where to render the table of contents. Place an empty div with a unique class into your theme template or use this block itself by settings it to <code>.js-toc-block</code>.'),
    ];
    $form['tocbot_js_settings']['content_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('contentSelector'),
      '#default_value' => $settings->get('content_selector'),
      '#description' => $this->t('Where to grab the headings to build the table of contents.'),
    ];
    $form['tocbot_js_settings']['heading_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('headingSelector'),
      '#default_value' => $settings->get('heading_selector'),
      '#description' => $this->t('Comma and space separated <code>h2, h3, h4, h5, h6</code>'),
    ];
    $form['tocbot_js_settings']['ignore_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ignoreSelector'),
      '#default_value' => $settings->get('ignore_selector'),
      '#description' => $this->t('Headings that match the ignoreSelector will be skipped.'),
    ];
    $form['tocbot_js_settings']['link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('linkClass'),
      '#default_value' => $settings->get('link_class'),
      '#description' => $this->t('Main class to add to links.'),
    ];
    $form['tocbot_js_settings']['extra_link_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('extraLinkClasses'),
      '#default_value' => $settings->get('extra_link_classes'),
      '#description' => $this->t('Extra classes to add to links.'),
    ];
    $form['tocbot_js_settings']['active_link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('activeLinkClass'),
      '#default_value' => $settings->get('active_link_class'),
      '#description' => $this->t('Class to add to active links, the link corresponding to the top most heading on the page.'),
    ];
    $form['tocbot_js_settings']['list_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('listClass'),
      '#default_value' => $settings->get('list_class'),
      '#description' => $this->t('Main class to add to lists.'),
    ];
    $form['tocbot_js_settings']['extra_list_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('extraListClasses'),
      '#default_value' => $settings->get('extra_list_classes'),
      '#description' => $this->t('Extra classes to add to lists.'),
    ];
    $form['tocbot_js_settings']['is_collapsed_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('isCollapsedClass'),
      '#default_value' => $settings->get('is_collapsed_class'),
      '#description' => $this->t('Class that gets added when a list should be collapsed.'),
    ];
    $form['tocbot_js_settings']['collapsible_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('collapsibleClass'),
      '#default_value' => $settings->get('collapsible_class'),
      '#description' => $this->t('Class that gets added when a list should be able to be collapsed but is not necessarily collapsed.'),
    ];
    $form['tocbot_js_settings']['list_item_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('listItemClass'),
      '#default_value' => $settings->get('list_item_class'),
      '#description' => $this->t('Class to add to list items.'),
    ];
    $form['tocbot_js_settings']['collapse_depth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('collapseDepth (number)'),
      '#default_value' => $settings->get('collapse_depth'),
      '#description' => $this->t('Class to add to list items.'),
    ];
    $form['tocbot_js_settings']['ordered_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('orderedList'),
      '#default_value' => $settings->get('ordered_list'),
      '#description' => $this->t('OrderedList can be set to false to generate unordered lists (ul) instead of ordered lists (ol).'),
    ];
    $form['tocbot_js_settings']['scroll_smooth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('scrollSmooth'),
      '#default_value' => $settings->get('scroll_smooth'),
      '#description' => $this->t('Smooth scrolling enabled.'),
    ];
    $form['tocbot_js_settings']['scroll_smooth_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('scrollSmoothDuration (number)'),
      '#default_value' => $settings->get('scroll_smooth_duration'),
      '#description' => $this->t('Smooth scroll duration.'),
    ];
    $form['tocbot_js_settings']['throttle_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('throttleTimeout (number)'),
      '#default_value' => $settings->get('throttle_timeout'),
      '#description' => $this->t('Timeout between events firing to make sure its not too rapid (for performance reasons)'),
    ];
    $form['tocbot_js_settings']['position_fixed_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('positionFixedSelector'),
      '#default_value' => $settings->get('position_fixed_selector'),
      '#description' => $this->t('Element to add the positionFixedClass to such as the .js-toc-block class.'),
    ];
    $form['tocbot_js_settings']['position_fixed_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('positionFixedClass'),
      '#default_value' => $settings->get('position_fixed_class'),
      '#description' => $this->t('Fixed position class to add to make sidebar fixed after scrolling down past the fixedSidebarOffset.'),
    ];
    $form['tocbot_js_settings']['fixed_sidebar_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('fixedSidebarOffset'),
      '#default_value' => $settings->get('fixed_sidebar_offset'),
      '#description' => $this->t('fixedSidebarOffset can be any number but by default is set to auto which sets the fixedSidebarOffset to the sidebar elements offsetTop from the top of the document on init.'),
    ];
    $form['tocbot_js_settings']['include_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('includeHtml'),
      '#default_value' => $settings->get('include_html'),
      '#description' => $this->t('includeHtml can be set to true to include the HTML markup from the heading node instead of just including the textContent.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $fieldsToSave = array_keys(TocbotHelper::getSettingsOptions());

    $config = $this->config('tocbot.settings');
    foreach ($fieldsToSave as $field) {
      $config->set($field, $form_state->getvalue($field));
    }
    $config->save();

    $this->messenger()->addMessage($this->t('The tocbot settings have been saved.'));
  }

}
