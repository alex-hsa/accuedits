<?php

namespace Drupal\preview\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\preview\Event\PreviewBackLink;
use Drupal\preview\Event\PreviewEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Contains a form for switching the view mode of an entity during preview.
 */
class PreviewForm extends FormBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('event_dispatcher'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Constructs a new NodePreviewForm.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, RouteProviderInterface $route_provider) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->configFactory = $config_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'preview_form_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    $view_mode = $entity->preview_view_mode;
    $entity_type = $entity->getEntityType();
    $bundle_key = $entity_type->getKey('bundle');

    $query_options = ['query' => ['uuid' => $entity->uuid()]];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $query_options['query']['destination'] = $query->get('destination');
    }

    $back_url = NULL;
    if ($entity->isNew()) {
      if (count($this->routeProvider->getRoutesByNames([$entity_type->id() . '.add'])) === 1) {
        $back_url = Url::fromRoute($entity_type->id() . '.add', [$bundle_key => $entity->bundle()]);
      }
    }
    elseif ($entity->hasLinkTemplate('edit-form')) {
      $back_url = $entity->toUrl('edit-form');
    }

    // Allow other modules to alter the back link.
    $event = new PreviewBackLink($entity, $back_url);
    $this->eventDispatcher->dispatch($event, PreviewEvents::PREVIEW_BACK_LINK);
    $back_url = $event->getBackLink();

    if ($back_url) {
      $form['backlink'] = [
        '#type' => 'link',
        '#title' => $this->t('Back to editing'),
        '#url' => $back_url,
        '#options' => ['attributes' => ['class' => ['preview-backlink']]] + $query_options,
      ];
    }

    // Always show full as an option, even if the display is not enabled.
    $view_mode_options = ['default' => $this->t('Default')] + $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type->id(), $entity->bundle());

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $entity->uuid(),
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $view_mode_options,
      '#default_value' => $view_mode,
      '#attributes' => [
        'data-drupal-autosubmit' => TRUE,
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
      '#attributes' => [
        'class' => ['js-hide'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_parameters = [
      'entity_preview' => $form_state->getValue('uuid'),
      'view_mode_id' => $form_state->getValue('view_mode'),
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('preview.entity_preview', $route_parameters, $options);
  }

}
