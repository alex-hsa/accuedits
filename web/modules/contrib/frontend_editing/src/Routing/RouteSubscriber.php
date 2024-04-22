<?php

namespace Drupal\frontend_editing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber for frontend editing module.
 *
 * @package Drupal\frontend_editing\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Extend entity preview route.
    if ($route = $collection->get('preview.entity_preview')) {
      $route->setDefault('_controller', '\Drupal\frontend_editing\Controller\PreviewController::view');
    }
  }

}
