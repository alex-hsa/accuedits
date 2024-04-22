<?php

namespace Drupal\frontend_editing\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\preview\Controller\PreviewController as PreviewControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the preview controller.
 */
class PreviewController extends PreviewControllerBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity_preview, $view_mode_id = 'default', $langcode = NULL, Request $request = NULL) {
    $build = parent::view($entity_preview, $view_mode_id, $langcode);
    // In case it is ajax request, respond with ajax response.
    if ($request->isXmlHttpRequest()) {
      // Remove any validation error messages from frontend editing form.
      // If we are here, then the preview is valid and we don't want to show
      // any error messages.
      $this->messenger->deleteByType('error');
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-preview="' . $entity_preview->uuid() . '"]', $build));
      return $response;
    }
    return $build;
  }

}
