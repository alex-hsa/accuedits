<?php

namespace Drupal\content_templates\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TemplateController for template routes.
 *
 * @package Drupal\content_templates\Controller
 */
class TemplateController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * Checks if user has access to the node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session.
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   *   Whether access is allowed or forbidden.
   */
  public function createAccess(AccountInterface $account, NodeInterface $node) {
    if (!$node->isNew()) {
      return AccessResult::allowedIfHasPermissions($account, [
        'add content template entities',
        'clone ' . $node->bundle() . ' content',
      ]);
    }
    return AccessResult::forbidden();
  }

  /**
   * The _title_callback for the content_templates.node.template route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function templatePageTitle(NodeInterface $node) {
    $node_template = $this->findTemplateByNode($node);
    if ($node_template->isNew()) {
      return $this->t('Create template from @title', ['@title' => $node->getTitle()]);
    }
    return $this->t('Edit template: @title', ['@title' => $node_template->label()]);
  }

  /**
   * The controller for the content_templates.node.template route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @return array
   *   Form for the node edit/add operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function addEdit(NodeInterface $node) {
    $node_template = $this->findTemplateByNode($node);

    // Get the form object for the entity defined in entity definition.
    $form_object = $this->entityTypeManager()->getFormObject($node_template->getEntityTypeId(), 'default');

    // Assign the form's entity to our duplicate!
    $form_object->setEntity($node_template);

    $form_state = (new FormState())->setFormState([]);
    return $this->formBuilder()->buildForm($form_object, $form_state);
  }

  /**
   * The _title_callback for the content_templates.node.overview route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function overviewPageTitle(NodeInterface $node) {
    $node_template = $this->findTemplateByNode($node);
    return $this->t('Content created from "@title" template', ['@title' => $node_template->label()]);
  }

  /**
   * The overview of content created from this template.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The source of template.
   *
   * @return array
   *   The list of items as table.
   */
  public function overview(NodeInterface $node) {
    $headers = [
      'title' => [
        'data' => $this->t('Title'),
        'field' => 'title',
      ],
      'author' => [
        'data' => $this->t('Author'),
        'field' => 'uid',
      ],
      'created' => [
        'data' => $this->t('Created'),
        'field' => 'created',
        'sort' => 'desc',
      ],
      'actions' => $this->t('Actions'),
    ];
    $query = $this->database->select('node_field_data', 'n');
    $query->condition('template', $node->id());
    $query->condition('default_langcode', 1);
    $query->fields('n');
    $query = $query->extend(PagerSelectExtender::class)->limit(25);
    $query = $query->extend(TableSortExtender::class)->orderByHeader($headers);
    $results = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);
    $rows = [];
    foreach ($results as $result) {
      $rows[] = [
        'title' => [
          'data' => Link::fromTextAndUrl($result['title'], Url::fromRoute('entity.node.canonical', ['node' => $node->id()]))->toRenderable(),
        ],
        'author' => [
          'data' => [
            '#theme' => 'username',
            '#account' => $this->entityTypeManager()->getStorage('user')->load($result['uid']),
          ],
        ],
        'created' => $this->dateFormatter->format($result['created']),
        'actions' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => [
              'view' => [
                'title' => $this->t('View'),
                'url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
              ],
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]),
              ],
            ],
          ],
        ],
      ];
    }
    $build['table'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No content found.'),
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Checks access to overview of content created from template.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function overviewAccess(AccountInterface $account, NodeInterface $node) {
    $template = $this->findTemplateByNode($node);
    $result = AccessResult::forbidden();
    if (!$template->isNew()) {
      $result = AccessResult::allowedIfHasPermission($account, 'access content from template overview');
    }
    return $result->addCacheableDependency($node);
  }

  /**
   * Find template by source node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Content template object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function findTemplateByNode(NodeInterface $node) {
    $template_storage = $this->entityTypeManager()->getStorage('content_template');
    $content_template = $template_storage->create(['field_source' => $node]);
    $query = $template_storage->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('field_source.target_id', $node->id());
    $ids = $query->execute();
    $content_templates = $template_storage->loadMultiple($ids);
    if (!empty($content_templates)) {
      $content_template = reset($content_templates);
    }
    return $content_template;
  }

  /**
   * The controller for the content_templates.node.add route.
   *
   * @return array
   *   Render array for "From template" page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function fromTemplate() {
    $template_storage = $this->entityTypeManager()->getStorage('content_template');
    $query = $template_storage->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('status', 1);
    $query->sort('name');
    $ids = $query->execute();
    $templates = $template_storage->loadMultiple($ids);
    $content_types = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'template-list',
      ],
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
        'tags' => [
          'taxonomy_term_list:template_category',
        ],
      ],
      '#attached' => [
        'library' => 'content_templates/admin',
      ],
    ];
    if (!empty($templates)) {
      $category_weights = [];
      foreach ($templates as $template) {
        $bundle = $template->get('field_source')->entity->bundle();
        if (!$this->currentUser()
          ->hasPermission('clone ' . $bundle . ' content')) {
          continue;
        }
        $category_id = 0;
        $category = FALSE;
        if (!$template->get('field_category')->isEmpty()) {
          /** @var \Drupal\taxonomy\TermInterface $category */
          $category = $template->get('field_category')->entity;
          if ($category) {
            $category_id = $category->id();
            $category_weights[$category_id] = $category->getWeight();
          }
        }
        if (empty($content_types[$category_id])) {
          $content_types[$category_id] = [
            '#theme' => 'item_list',
            '#attributes' => ['class' => 'category-list'],
            '#items' => [],
            '#title' => $category ? $category->label() : $this->t('Uncategorized'),
          ];
        }
        $content_types[$category_id]['#items'][] = $this->entityTypeManager()
          ->getViewBuilder('content_template')
          ->view($template);
      }
      if (!empty($category_weights)) {
        if (!empty($content_types[0])) {
          // Make the templates without category to be displayed as last.
          $category_weights[0] = 1000;
        }
        asort($category_weights);
        $sorted_category_ids = array_keys($category_weights);
        // Sort templates and categories.
        $content_types = array_replace(array_flip($sorted_category_ids), $content_types);
      }
    }
    else {
      $content_types['empty'] = [
        '#markup' => $this->t('There are no templates at the moment'),
      ];
    }
    return $content_types;
  }

}
