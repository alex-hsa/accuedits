<?php

namespace Drupal\augmentor\Controller;

use Drupal\augmentor\AugmentorManager;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the route to executes augmentors with a given input via ajax.
 */
class AugmentorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The csrf token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The Augmentor plugin manager.
   *
   * @var \Drupal\augmentor\AugmentorManager
   */
  protected $augmentorManager;

  /**
   * The Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a AugmentorController object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Drupal\augmentor\AugmentorManager $augmentor_manager
   *   The Augmentor plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    CsrfTokenGenerator $csrf_token_generator,
    AugmentorManager $augmentor_manager,
    RequestStack $request_stack,
    ModuleHandlerInterface $module_handler) {
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->augmentorManager = $augmentor_manager;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('plugin.manager.augmentor.augmentors'),
      $container->get('request_stack'),
      $container->get('module_handler'),
    );
  }

  /**
   * Take the incoming data and hand it over for processing.
   *
   * @return string
   *   HTTP response, to be processed by the augmentor_library.js.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function execute() {
    try {
      $decoded_request_body = Json::decode($this->requestStack->getCurrentRequest()->getContent());
      // Call Pre Execute hooks to alter the processing input and augmentor.
      $this->moduleHandler->invokeAll('pre_execute', [&$decoded_request_body]);
      $result = $this->augmentorManager->executeAugmentor(
        $decoded_request_body['augmentor'],
        $decoded_request_body['input']
      );
      // Call Post Execure hooks to alter the results.
      $this->moduleHandler->invokeAll('post_execute', [&$result]);
    }
    catch (\Throwable $error) {
      $result = ['_errors' => $error->getMessage()];
    }

    if (array_key_exists('_errors', $result)) {
      return new JsonResponse(Json::encode(
        $result['_errors'],
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
      ), 400);
    }

    return new JsonResponse(
      Json::encode(
        $result,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
      )
    );
  }

}
