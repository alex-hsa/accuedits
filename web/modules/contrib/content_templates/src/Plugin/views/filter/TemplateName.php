<?php

namespace Drupal\content_templates\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for template names.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("template_name")
 */
class TemplateName extends InOperator {

  /**
   * Flag for always multiple.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * The validated exposed input.
   */
  protected array $validated_exposed_input;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $nodes = $this->value ? Node::loadMultiple($this->value) : [];
    $default_value = EntityAutocomplete::getEntityLabels($nodes);
    $form['value'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Templates'),
      '#target_type' => 'node',
      '#tags' => TRUE,
      '#default_value' => $default_value,
      '#process_default_value' => $this->isExposed(),
    ];

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $nids = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $nids[] = $value['target_id'];
      }
      sort($nids);
    }
    $form_state->setValue(['options', 'value'], $nids);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $rc = parent::acceptExposedInput($input);

    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    $input = $form_state->getValue($identifier);

    if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
      $this->operator = $this->options['group_info']['group_items'][$input]['operator'];
      $input = $this->options['group_info']['group_items'][$input]['value'];
    }

    $nids = [];
    $values = $form_state->getValue($identifier);
    if ($values && (!$this->options['is_grouped'] || ($this->options['is_grouped'] && ($input != 'All')))) {
      foreach ($values as $value) {
        $nids[] = $value['target_id'];
      }
    }

    if ($nids) {
      $this->validated_exposed_input = $nids;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    // Set up $this->valueOptions for the parent summary.
    $this->valueOptions = [];

    if ($this->value) {
      $result = \Drupal::entityTypeManager()->getStorage('node')
        ->loadByProperties(['nid' => $this->value]);
      foreach ($result as $node) {
        $this->valueOptions[$node->id()] = $node->label();
      }
    }

    return parent::adminSummary();
  }

}
