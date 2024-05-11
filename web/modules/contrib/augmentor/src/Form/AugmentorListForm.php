<?php

namespace Drupal\augmentor\Form;

use Drupal\augmentor\AugmentorManager;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Augmentor list form.
 */
class AugmentorListForm extends ConfigFormBase {

  /**
   * The augmentor manager service.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorAugmentorManager;

  /**
   * Constructs a AugmentorListForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The augmentor manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AugmentorManager $augmentor_manager) {
    parent::__construct($config_factory);
    $this->augmentorAugmentorManager = $augmentor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.augmentor.augmentors'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'augmentor_list_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'augmentor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('augmentor.settings');
    $form['#title'] = $this->t('Augmentors');
    $form['#tree'] = TRUE;
    $count = 0;
    $new_augmentor_options = [];
    $augmentors = $this->augmentorAugmentorManager->getDefinitions();
    uasort($augmentors, function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });

    foreach ($augmentors as $augmentor => $definition) {
      $new_augmentor_options[$augmentor] = $definition['label'];
    }

    // Build the list of existing augmentors.
    $form['augmentors'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'augmentor-augmentor-order-weight',
      ],
      ],
      '#empty' => $this->t('There are currently no augmentors in the system.'),
    ];

    if ($augmentors = $config->get('augmentors')) {
      $weight_delta = round(count($augmentors) / 2);
      foreach ($augmentors as $key => $augmentor) {
        $count++;
        $form['augmentors'][$key]['#attributes']['class'][] = 'draggable';
        $form['augmentors'][$key]['#weight'] = isset($user_input['augmentors']) ? $user_input['augmentors'][$key]['weight'] : $augmentor['weight'];
        $form['augmentors'][$key]['augmentor'] = [
          '#tree' => FALSE,
          'settings' => [
            'label' => [
              '#plain_text' => $augmentor['label'],
            ],
          ],
        ];

        $form['augmentors'][$key]['type'] = [
          '#tree' => FALSE,
          'settings' => [
            'label' => [
              '#plain_text' => $new_augmentor_options[$augmentor['type']],
            ],
          ],
        ];

        $form['augmentors'][$key]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @key', ['@key' => $key]),
          '#title_display' => 'invisible',
          '#default_value' => $augmentor['weight'] ?? 0,
          '#delta' => $weight_delta,
          '#attributes' => [
            'class' => ['augmentor-augmentor-order-weight'],
          ],
        ];

        $links = [];

        if ($key) {
          $links['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('augmentor.augmentor_edit_form', [
              'augmentor' => $key,
            ]),
          ];
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('augmentor.augmentor_delete_form', [
              'augmentor' => $key,
            ]),
          ];
        }

        $form['augmentors'][$key]['operations'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
      }
    }

    // Build the new augmentor addition form and
    // add it to the augmentor list.
    $form['augmentors']['new'] = [
      '#tree' => FALSE,
      '#weight' => $user_input['weight'] ?? NULL,
      '#attributes' => ['class' => ['draggable']],
    ];
    $form['augmentors']['new']['augmentor'] = [
      'settings' => [
        'new' => [
          '#type' => 'select',
          '#title' => $this->t('Augmentor'),
          '#title_display' => 'invisible',
          '#options' => $new_augmentor_options,
          '#empty_option' => $this->t('Select augmentor type'),
        ],
        [
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#validate' => ['::augmentorValidate'],
            '#submit' => ['::submitForm', '::augmentorSave'],
          ],
        ],
      ],
      '#prefix' => '<div class="augmentor-new">',
      '#suffix' => '</div>',
    ];

    $form['augmentors']['new']['type'] = [
      'settings' => [],
    ];

    $form['augmentors']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new augmentor'),
      '#title_display' => 'invisible',
      '#default_value' => $count,
      '#attributes' => ['class' => ['augmentor-augmentor-order-weight']],
    ];
    $form['augmentors']['new']['operations'] = [
      'settings' => [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('augmentor.settings');
    $augmentors = [];

    if (!$form_state->isValueEmpty('augmentors')) {
      foreach ($form_state->getValue('augmentors') as $key => $augmentor) {
        $augmentors[$key] = $config->get('augmentors.' . $key);
        $augmentors[$key]['weight'] = $augmentor['weight'];
      }
    }

    $config->set('augmentors', $augmentors);
    $config->save();
  }

  /**
   * Validate handler for augmentor.
   */
  public function augmentorValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setErrorByName('new', $this->t('Select a augmentor to add.'));
    }
  }

  /**
   * Submit handler for augmentor.
   */
  public function augmentorSave($form, FormStateInterface $form_state) {
    // $this->save($form, $form_state);
    // Load the configuration form for this option.
    $form_state->setRedirect(
    'augmentor.augmentor_add_form', [
      'augmentor' => $form_state->getValue('new'),
    ], [
      'query' => ['weight' => $form_state->getValue('weight')],
    ]
    );
  }

}
