<?php

namespace Drupal\frontend_editing\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\frontend_editing\Ajax\ReloadWindowCommand;
use Drupal\frontend_editing\ParagraphsHelperInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Frontend editing form.
 *
 * @package Drupal\frontend_editing\Controller
 */
class FrontendEditingController extends ControllerBase {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $builder;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The paragraphs helper.
   *
   * @var \Drupal\frontend_editing\ParagraphsHelperInterface
   */
  protected $paragraphsHelper;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * FrontendEditingController constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Entity\EntityFormBuilder $builder
   *   Entity form builder.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\frontend_editing\ParagraphsHelperInterface $paragraphs_helper
   *   The paragraphs' helper.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data storage.
   */
  public function __construct(RendererInterface $renderer, EntityFormBuilder $builder, EntityRepositoryInterface $entity_repository, ParagraphsHelperInterface $paragraphs_helper, UserDataInterface $userData) {
    $this->renderer = $renderer;
    $this->builder = $builder;
    $this->entityRepository = $entity_repository;
    $this->paragraphsHelper = $paragraphs_helper;
    $this->userData = $userData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity.form_builder'),
      $container->get('entity.repository'),
      $container->get('frontend_editing.paragraphs_helper'),
      $container->get('user.data')
    );
  }

  /**
   * Toggle frontend editing functionality.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function toggle(Request $request) {
    if (!$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }
    // Get current state.
    $current_state = (bool) $this->userData->get('frontend_editing', $this->currentUser()->id(), 'enabled');
    // Revert it, as requested.
    $new_state = !$current_state;
    // Set the new value.
    $this->userData->set('frontend_editing', $this->currentUser()->id(), 'enabled', $new_state);
    // Prepare the response.
    $response = new AjaxResponse();
    if ($new_state) {
      $message = $this->t('Frontend editing has been enabled.');
      $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'addClass', ['frontend-editing--enabled']));
      $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'text', [$this->t('On')]));
      $response->addCommand(new InvokeCommand('body', 'removeClass', ['frontend-editing--hidden']));
    }
    else {
      $message = $this->t('Frontend editing has been disabled.');
      $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'removeClass', ['frontend-editing--enabled']));
      $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'text', [$this->t('Off')]));
      $response->addCommand(new InvokeCommand('body', 'addClass', ['frontend-editing--hidden']));
    }
    $response->addCommand(new MessageCommand($message, NULL, ['type' => 'status']));
    $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'attr', [
      'data-toggle-state',
      $new_state,
    ]));
    $response->addCommand(new InvokeCommand('#frontend-editing-toggle-link', 'removeClass', ['frontend-editing-toggle-not-configured']));

    return $response;
  }

  /**
   * Implements form load request handler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   * @param string $type
   *   Entity type.
   * @param int $id
   *   Entity id.
   * @param string $display
   *   Form operation.
   *
   * @return array
   *   Form array.
   */
  public function getForm(Request $request, $type, $id, $display = 'default') {
    // Load the form and render.
    try {
      $storage = $this->entityTypeManager()->getStorage($type);
    }
    catch (PluginNotFoundException $exception) {
      $this->messenger()->addError($exception->getMessage());
      return [];
    }
    $entity = $storage->load($id);
    if (!$entity) {
      $this->messenger()->addWarning($this->t('Entity of type @type and id @id was not found',
        ['@type' => $type, '@id' => $id]
      ));
      return [];
    }
    // Remove all messages.
    $this->messenger()->deleteAll();
    // If the entity type is translatable, ensure we use the proper entity
    // translation for the current context, so that the access check is made on
    // the entity translation.
    $entity = $this->entityRepository->getTranslationFromContext($entity);
    $form_state_additions = [];
    switch ($display) {
      case 'default':
        if (!$entity->access('update')) {
          throw new AccessDeniedHttpException();
        }

        if ($entity instanceof ParagraphInterface) {
          $display = 'entity_edit';

          // Paragraphs cannot be saved through frontend editing when before the
          // save the user has interacted with the form in a way that it was
          // cached - e.g. by using AJAX to exchange an element or to add a new
          // element. An example is a block reference paragraph, where when
          // selecting a new reference from the select list an Ajax request will
          // be triggered.
          //
          // On submitting the form the cached form object will be used for
          // further processing. The problem is that the cached form object
          // (ParagraphEditForm) does not have the class property $root_parent
          // set as this is set only when accessing the form through the route
          // “paragraphs_edit.edit_form”, however the current implementation of
          // Frontend Editing only uses that route to submit the form to
          // (manipulates the form action before returning the form). AJAX
          // interactions with the form however go through the route
          // “xi_frontend_editing.form”, which misses the route parameter
          // “root_parent” and then the form object is cached without the
          // corresponding class property being set. The AJAX interactions are
          // routed through “xi_frontend_editing.form” because the paragraph
          // form is retrieved initially from that route and the AJAX system
          // uses the current route when building the ajax elements.
          // @see \Drupal\Core\Render\Element\RenderElement::preRenderAjaxForm()
          //
          // One solution is to ensure that the Frontend Editing passes the host
          // entity to the form build args when retrieving the form for the
          // paragraph. This however is still not a perfect solution, as the
          // “xi_frontend_editing.form” route will further be used for form
          // interactions, but the form will be routed somewhere else for
          // submission.
          $root_parent = $this->paragraphsHelper->getParagraphRootParent($entity);
          $form_state_additions = ['build_info' => ['args' => ['root_parent' => $root_parent]]];

          $url = Url::fromRoute('paragraphs_edit.edit_form', [
            'root_parent_type' => $root_parent->getEntityTypeId(),
            'root_parent' => $root_parent->id(),
            'paragraph' => $entity->id(),
          ]);
        }
        else {
          $url = Url::fromRoute('entity.' . $type . '.edit_form', [$type => $id]);
        }

        $entityForm = $this->builder->getForm($entity, $display, $form_state_additions);
        $entityForm['#action'] = $url->toString();

        $delete_url = Url::fromRoute('frontend_editing.form', [
          'id' => $entity->id(),
          'type' => $entity->getEntityTypeId(),
          'display' => 'delete',
        ]);
        $delete_access = $entity->isNew() || $entity->access('delete');
        if ($entity instanceof ParagraphInterface) {
          $parent_field_name = $entity->get('parent_field_name')->value;
          $parent_entity = $entity->getParentEntity();
          $parent_field_definition = $parent_entity->get($parent_field_name)->getFieldDefinition();
          if ($parent_entity->isTranslatable() && !$parent_entity->isDefaultTranslation() && !$parent_field_definition->isTranslatable()) {
            $delete_access = FALSE;
          }
        }
        $entityForm['actions']['delete'] = [
          '#type' => 'link',
          '#title' => $this->t('Delete'),
          '#url' => $delete_url,
          '#access' => $delete_access,
          '#attributes' => [
            'class' => ['button', 'button--danger'],
          ],
          '#weight' => 10,
        ];
        break;

      case 'delete':
        if (!$entity->access('delete')) {
          throw new AccessDeniedHttpException();
        }
        if ($entity instanceof ParagraphInterface) {
          // By default, paragraph reference fields do not support translations.
          // For this reason we need to check in case the parent entity is
          // translatable that current translation is the default one and that
          // the paragraph field supports translations. In other case do not
          // allow to delete paragraph, because it will break the sync
          // translation and the paragraph will be deleted for all translations.
          // There are modules that allow async translation of paragraphs. In
          // this case it will still be possible to do, because then the field
          // definition will identify that the field is translatable.
          $parent_field_name = $entity->get('parent_field_name')->value;
          $parent_entity = $entity->getParentEntity();
          $parent_field_definition = $parent_entity->get($parent_field_name)->getFieldDefinition();
          if ($parent_entity->isTranslatable() && !$parent_entity->isDefaultTranslation() && !$parent_field_definition->isTranslatable()) {
            throw new AccessDeniedHttpException('You are not allowed to delete this paragraph, because paragraph parent field is not translatable.');
          }
          $display = 'entity_delete';
          $root_parent = $this->paragraphsHelper->getParagraphRootParent($entity);
          $form_state_additions['build_info']['args']['root_parent'] = $root_parent;

          $url = Url::fromRoute('paragraphs_edit.delete_form', [
            'root_parent_type' => $root_parent->getEntityTypeId(),
            'root_parent' => $root_parent->id(),
            'paragraph' => $entity->id(),
          ]);
        }
        else {
          $url = Url::fromRoute('entity.' . $type . '.delete_form', [$type => $id]);
        }
        $entityForm = $this->builder->getForm($entity, $display, $form_state_additions);
        $entityForm['title'] = [
          '#markup' => '<h3>' . $this->t('Are you sure you want to delete this @type?', ['@type' => $entity->getEntityType()->getSingularLabel()]) . '</h3>',
          '#weight' => -10,
        ];
        $entityForm['#action'] = $url->toString();
        break;
    }
    $entityForm['#attached']['library'][] = 'frontend_editing/forms_helper';
    return $entityForm;
  }

  /**
   * Checks access to move paragraph up request.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to move.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessUp(ParagraphInterface $paragraph) {
    return $this->paragraphsHelper->allowUp($paragraph);
  }

  /**
   * Checks access to add paragraph to parent.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type
   *   The paragraph to move.
   * @param string $parent_type
   *   The parent entity type.
   * @param mixed $parent
   *   The parent id.
   * @param string $parent_field_name
   *   The parent field name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessAddType(ParagraphsTypeInterface $paragraphs_type, $parent_type, $parent, $parent_field_name) {
    return $this->paragraphsHelper->allowAddType($paragraphs_type, $parent_type, $parent, $parent_field_name);
  }

  /**
   * Checks access to add paragraph to parent.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessAdd($parent_type, $parent, $parent_field_name) {
    return $this->paragraphsHelper->allowAdd($parent_type, $parent, $parent_field_name);
  }

  /**
   * Checks access to move paragraph down request.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to move.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessDown(ParagraphInterface $paragraph) {
    return $this->paragraphsHelper->allowDown($paragraph);
  }

  /**
   * Checks access to update content.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param int $entity_id
   *   The entity id.
   * @param string $field_name
   *   The field name.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessUpdateContent($entity_type_id, $entity_id, $field_name, $view_mode) {
    $entity = $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    if (!$entity) {
      $result = AccessResult::forbidden('Entity does not exist.');
    }
    elseif (!$entity->access('view')) {
      $result = AccessResult::forbidden('You are not allowed to view this entity.');
    }
    elseif (!$entity->hasField($field_name)) {
      $result = AccessResult::forbidden('Entity has no field ' . $field_name . ' .');
    }
    elseif (!$entity->get($field_name)->access('view')) {
      $result = AccessResult::forbidden('You are not allowed to view field ' . $field_name . ' .');
    }
    else {
      $result = AccessResult::allowed();
    }
    return $result->addCacheableDependency($entity)->cachePerPermissions();
  }

  /**
   * Shift up a single paragraph.
   */
  public function up(ParagraphInterface $paragraph, Request $request) {
    $message = FALSE;
    if (!$this->paragraphsHelper->move($paragraph, 'up')) {
      $message = $this->t('The paragraph could not be moved up.');
    }
    if ($request->isXmlHttpRequest()) {
      return $this->ajaxUpdateParagraphs($paragraph, $message, $request->get('view_mode_id', 'default'));
    }
    if (!empty($message)) {
      $this->messenger()->addError($message);
    }
    return new RedirectResponse($this->paragraphsHelper->getRedirectUrl($paragraph)->toString());
  }

  /**
   * Shift down a single paragraph.
   */
  public function down(ParagraphInterface $paragraph, Request $request) {
    $message = FALSE;
    if (!$this->paragraphsHelper->move($paragraph, 'down')) {
      $message = $this->t('The paragraph could not be moved down.');
    }
    if ($request->isXmlHttpRequest()) {
      return $this->ajaxUpdateParagraphs($paragraph, $message, $request->get('view_mode_id', 'default'));
    }
    return new RedirectResponse($this->paragraphsHelper->getRedirectUrl($paragraph)->toString());
  }

  /**
   * Ajax callback to update paragraphs.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to update.
   * @param string $message
   *   The message to display.
   * @param string $view_mode_id
   *   The view mode of the paragraphs field.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function ajaxUpdateParagraphs(ParagraphInterface $paragraph, $message, $view_mode_id = 'default') {
    $response = new AjaxResponse();
    if ($message) {
      $response->addCommand(new MessageCommand($message, NULL, ['type' => 'error']));
    }
    $updated_content = $paragraph->getParentEntity()->get($paragraph->get('parent_field_name')->value)->view($view_mode_id);
    $selector = '[data-frontend-editing="' . $paragraph->getParentEntity()->getEntityTypeId() . '--' . $paragraph->getParentEntity()->id() . '--' . $paragraph->get('parent_field_name')->value . '--' . $view_mode_id . '"]';
    $response->addCommand(new HtmlCommand($selector, $updated_content));
    return $response;
  }

  /**
   * Update content with ajax.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param mixed $entity_id
   *   The entity id.
   * @param string $field_name
   *   The field name.
   * @param string $view_mode
   *   The view mode.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function updateContent($entity_type_id, $entity_id, $field_name, $view_mode, Request $request) {
    if (!$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }
    $response = new AjaxResponse();
    if (empty($view_mode)) {
      $view_mode = 'default';
    }
    $entity = NULL;
    try {
      $entity = $this->entityTypeManager()
        ->getStorage($entity_type_id)
        ->load($entity_id);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $message = $this->t('Entity of type @type and id @id was not found',
        ['@type' => $entity_type_id, '@id' => $entity_id]
      );
      $response->addCommand(new MessageCommand($message, NULL, ['type' => 'error']));
    }
    if (!$entity) {
      $message = $this->t('Entity of type @type and id @id was not found',
        ['@type' => $entity_type_id, '@id' => $entity_id]
      );
      $response->addCommand(new MessageCommand($message, NULL, ['type' => 'error']));
    }
    // If there are errors, early return and reload the page.
    if (!empty($response->getCommands())) {
      $response->addCommand(new ReloadWindowCommand());
      return $response;
    }
    $updated_content = $entity->get($field_name)->view($view_mode);
    $selector = '[data-frontend-editing="' . $entity_type_id . '--' . $entity_id . '--' . $field_name . '--' . $view_mode . '"]';
    $response->addCommand(new HtmlCommand($selector, $updated_content));
    return $response;
  }

  /**
   * Displays the list of paragraphs that are available for creation.
   *
   * The list is limited to the paragraphs that are allowed to be added to the
   * parent entity field.
   *
   * @param string $parent_type
   *   The parent entity type.
   * @param string $parent
   *   The parent id.
   * @param string $parent_field_name
   *   The parent field name.
   * @param int $current_paragraph
   *   The current paragraph id. The one that initiated the request.
   * @param int $before
   *   Could be 0 or 1. 1 means that the new paragraph should be added before
   *   current paragraph.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Render array with the list of paragraph types as links to add paragraph
   *   form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function paragraphAddPage($parent_type, $parent, $parent_field_name, $current_paragraph, $before, Request $request) {
    try {
      $parent_entity = $this->entityTypeManager()->getStorage($parent_type)
        ->load($parent);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      throw new NotFoundHttpException('Parent entity not found.');
    }
    if (!$parent_entity || !$parent_entity->hasField($parent_field_name)) {
      throw new NotFoundHttpException('Parent entity not found.');
    }
    // By default, assume that all paragraphs are allowed.
    $allowed_paragraphs = NULL;
    // Check the field settings.
    $settings = $parent_entity->get($parent_field_name)->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      $target_bundles = $settings['handler_settings']['target_bundles'];
      $allowed_paragraphs = array_filter($target_bundles);
    }
    // Check if the allowed paragraphs should be negated.
    if (!empty($settings['handler_settings']['negate'])) {
      $query = $this->entityTypeManager()
        ->getStorage('paragraphs_type')
        ->getQuery();
      $query->condition('id', $allowed_paragraphs, 'NOT IN');
      $query->accessCheck();
      $allowed_paragraphs = $query->execute();
    }
    $allowed_paragraphs = $this->entityTypeManager()
      ->getStorage('paragraphs_type')
      ->loadMultiple($allowed_paragraphs);
    $items = [];
    foreach ($allowed_paragraphs as $paragraphs_type) {
      $items[$paragraphs_type->id()] = [
        '#type' => 'link',
        '#title' => $this->t('Add @type', ['@type' => $paragraphs_type->label()]),
        '#url' => Url::fromRoute('frontend_editing.paragraph_add', [
          'parent_type' => $parent_type,
          'parent' => $parent,
          'parent_field_name' => $parent_field_name,
          'paragraphs_type' => $paragraphs_type->id(),
          'current_paragraph' => $current_paragraph,
          'before' => $before,
        ], [
          'query' => $request->query->all(),
        ]),
        '#attributes' => [
          'class' => 'field-add-more-submit button--small button js-form-submit form-submit',
          'name' => $parent_field_name . '_' . $paragraphs_type->id() . '_add_more',
        ],
        '#wrapper_attributes' => [
          'class' => ['paragraphs-add-dialog-row'],
        ],
      ];
    }
    if (!empty($settings['handler_settings']['target_bundles_drag_drop'])) {
      uasort($settings['handler_settings']['target_bundles_drag_drop'], function ($a, $b) {
        return $a['weight'] <=> $b['weight'];
      });
      if (!empty($settings['handler_settings']['negate'])) {
        $settings['handler_settings']['target_bundles_drag_drop'] = array_filter($settings['handler_settings']['target_bundles_drag_drop'], function ($value) {
          return !$value['enabled'];
        });
      }
      else {
        $settings['handler_settings']['target_bundles_drag_drop'] = array_filter($settings['handler_settings']['target_bundles_drag_drop'], function ($value) {
          return $value['enabled'];
        });
      }
      $items = array_replace(array_flip(array_keys($settings['handler_settings']['target_bundles_drag_drop'])), $items);
      $items = array_values($items);
    }
    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => [
        'class' => ['paragraphs-add-dialog-list'],
      ],
      '#attached' => [
        'library' => [
          'paragraphs/drupal.paragraphs.modal',
          'frontend_editing/forms_helper',
        ],
      ],
    ];
  }

}
