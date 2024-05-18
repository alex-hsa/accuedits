<?php

namespace Drupal\preview\ParamConverter;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\preview\PreviewFormService;

/**
 * Provides upcasting for a node entity in preview.
 */
class PreviewConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new PreviewConverter.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $store = $this->tempStoreFactory->get(PreviewFormService::TEMPSTORE_NAME);
    if ($form_state = $store->get($value)) {
      return $form_state->getFormObject()->getEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'entity_preview') {
      return TRUE;
    }
    return FALSE;
  }

}
