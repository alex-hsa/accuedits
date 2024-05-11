<?php

declare(strict_types = 1);

namespace Drupal\openai\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Administrative event subscriber for OpenAI.
 */
final class OpenAIEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs an OpenAIEventSubscriber object.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AdminContext $adminContext,
    private readonly MessengerInterface $messenger
  ) {}

  /**
   * Kernel request event handler.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $config = $this->configFactory->get('openai.settings');

    if ($this->adminContext->isAdminRoute() && empty($config->get('api_key'))) {
      $message = $this->t('You have not provided an OpenAI API key yet. This is required for its functionality to work. Please obtain an API key from <a href=":account">your OpenAI account</a> and add it to the <a href=":settings">OpenAI settings configuration here</a>.',
        [
          ':account' => 'https://platform.openai.com/',
          ':settings' => '/admin/config/openai/settings'
        ],
      );

      $this->messenger->addError($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
