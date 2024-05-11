<?php

namespace Drupal\augmentor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * An augmentor field widget.
 *
 * @FieldWidget(
 *   id = "augmentor_select_regex_widget",
 *   label = @Translation("Augmentor Select Regex Widget"),
 *   field_types = {
 *     "field_augmentor_type",
 *   }
 * )
 */
class AugmentorSelectRegexWidget extends AugmentorBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $augmentor_field_name = 'execute_' . $this->fieldDefinition->getName();
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['type'] = 'select_regex';
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['regex'] = $this->getSetting('regex');
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['result_pattern'] = $this->getSetting('result_pattern');
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['explode_separator'] = $this->getSetting('explode_separator');
    $form['#attached']['drupalSettings']['augmentor'][$augmentor_field_name]['data']['match_index'] = $this->getSetting('match_index');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'regex' => '/\d\. "(.*)"/gim',
      'result_pattern' => '$1',
      'explode_separator' => '\n',
      'match_index' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['regex'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Regex pattern'),
      '#default_value' => $this->getSetting('regex'),
      '#size' => 30,
      '#required' => TRUE,
      '#description' => $this->t('Regular expression pattern that will be used to parse the value from the returned result.'),
    ];

    $element['explode_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Explode separator'),
      '#default_value' => $this->getSetting('explode_separator'),
      '#size' => 10,
      '#description' => $this->t('Split the augmentor response into an array using this separator. If left empty, the regex pattern will find all matches. The separator can use regular expression syntax, including \\n to split on newlines.'),
    ];

    $element['result_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result pattern'),
      '#default_value' => $this->getSetting('result_pattern'),
      '#size' => 30,
      '#description' => $this->t('Optionally specify a regex pattern to use for results. If left blank, the matched pattern will be returned.'),
      '#states' => [
        'visible' => [
          ':input[name$="[explode_separator]"]' => ['empty' => FALSE],
        ],
      ],
    ];

    $element['match_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Match index'),
      '#default_value' => $this->getSetting('match_index'),
      '#size' => 10,
      '#description' => $this->t('Specify an index within the regex match array to use. If left blank, 0 (zero) will be used, as a match for the full pattern.'),
      '#states' => [
        'visible' => [
          [
            ':input[name$="[explode_separator]"]' => ['empty' => TRUE],
          ],
          'or',
          [
            ':input[name$="[result_pattern]"]' => ['empty' => TRUE],
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($regex = $this->getSetting('regex')) {
      $summary[] = $this->t('Regex pattern: "@regex"', ['@regex' => $regex]);
    }
    if ($result_pattern = $this->getSetting('result_pattern')) {
      $summary[] = $this->t('Result pattern: "@result_pattern"', ['@result_pattern' => $result_pattern]);
    }
    if ($separator = $this->getSetting('explode_separator')) {
      $summary[] = $this->t('Explode separator: "@separator"', ['@separator' => $separator]);
    }

    return $summary;
  }

}
