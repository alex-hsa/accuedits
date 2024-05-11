<?php

namespace Drupal\openai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\openai\OpenAIApi;
use OpenAI\Exceptions\TransporterException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles different admin screens in the OpenAI interface.
 */
class Admin extends ControllerBase {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * The admin controller constructor.
   *
   * @param \Drupal\openai\OpenAIApi $api
   *   The OpenAI API wrapper.
   */
  public function __construct(OpenAIApi $api) {
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai.api')
    );
  }

  /**
   * Display a list of available models.
   */
  public function listModels() {
    $build = [
      '#markup' => '<p>' . $this->t('Please note that the list of available models can change at any time and certain models do have deprecation schedules. Consult the documentation for more information. You can refresh this list manually by clearing the site cache.') . '</p>',
    ];

    try {
      $models = $this->api->getModels();

      $build[] = [
        '#theme' => 'item_list',
        '#items' => $models
      ];
    }
    catch (TransporterException | \Exception $e) {
      $build['#markup'] = $this->t('There was an issue obtaining models from OpenAI. Check the logs for more information.');
    }

    return $build;
  }

  /**
   * Redirect to OpenAI docs.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function docs() {
    return new TrustedRedirectResponse('https://platform.openai.com/docs');
  }

}
