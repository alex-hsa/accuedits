<?php

namespace Drupal\material_icons\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\material_icons\Traits\MaterialIconsSettings;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class Autocomplete extends ControllerBase {

  use MaterialIconsSettings;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Autocomplete constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \GuzzleHttp\Client $client
   *   The http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(CacheBackendInterface $cache, Client $client, ConfigFactoryInterface $configFactory) {
    $this->cache = $cache;
    $this->client = $client;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.data'),
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $font_family = $request->query->get('font_family');
    $font_set = $this->getFontSetFromFamily($font_family) ?? $this->getDefaultFontSet();

    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      // Load the icon data so we can check for a valid icon.
      $iconData = $this->getIconMeta($request, $font_set);

      // Check each icon to see if it starts with the typed string.
      if (!empty($iconData['icons'])) {
        foreach ($iconData['icons'] as $data) {
          $icon = $data['name'];

          // Starts with.
          if (strpos($icon, $typed_string) === 0) {
            $results[$icon] = [
              'value' => $icon,
              'label' => $this->getRenderableLabel($icon, $font_set),
              'weight' => 1,
            ];
          }
          // If the string is found.
          elseif (strpos($icon, $typed_string) === 0) {
            $results[$icon] = [
              'value' => $icon,
              'label' => $this->getRenderableLabel($icon, $font_set),
              'weight' => 2,
            ];
          }
        }

        // Add in tag matches as a helper.
        if (count($results) < 10) {
          foreach ($iconData['icons'] as $data) {
            $icon = $data['name'];
            $tags = !empty($data['tags']) ? $data['tags'] : [];

            foreach ($tags as $tag) {
              if (strpos($tag, $typed_string) === 0) {
                $results[$icon] = [
                  'value' => $icon,
                  'label' => $this->getRenderableLabel($icon, $font_set),
                  'weight' => 3,
                ];
              }
            }
          }
        }

        // Sort by weight.
        usort($results, function ($a, $b) {
          if ($a['weight'] === $b['weight']) {
            return 0;
          }
          return $a['weight'] < $b['weight'] ? -1 : 1;
        });
      }
    }
    return new JsonResponse(array_slice($results, 0, 10));
  }

  /**
   * Grabs icons from Google.
   *
   * @todo create a fallback.
   * @param $request
   *    The current request.
   * @param $font_set
   *    The active font set being used.
   * @return array
   *   The icon list.
   */
  protected function getIconMeta($request, $font_set) {

    // Gets default font set information.
    $cache_string = $this->getCacheString($font_set);
    $meta_url = $this->getFontSetMetaUrl($font_set);

    // Check for cached icons.
    if (!$icons = $this->cache->get($cache_string)) {
      // Parse the metadata file and use it to generate the icon list.
      $icons = [];
      // @todo fix handling exeptions here.
      $response = $this->client->get($meta_url);
      $status_code = $response->getStatusCode();
      if ($status_code === 200) {
        $body = $response->getBody()->getContents();

        // @todo Something is weird with the json response.
        $first_bracket = strpos($body, '{');
        if ($first_bracket !== 0) {
          $body = substr($body, $first_bracket);
        }

        if ($icons = JSON::decode($body)) {
          $this->cache->set($cache_string, $icons, strtotime('+1 week'), [
            'materialicons',
            'iconlist',
          ]);
        }
      }
    }
    else {
      $icons = $icons->data;
    }

    return (array) $icons;
  }

  /**
   * Helper to render icons in different families.
   *
   * @param string $icon
   *   The icon.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   A renderable markup object.
   */
  protected function getRenderableLabel($icon, $font_set) {
    $families = $this->configFactory->get('material_icons.settings')
      ->get('families');

    $font_set_families = array_keys($this->getFontSetFamilies($font_set));
    $font_families_intersect = array_intersect($families, $font_set_families);

    $icons_html = '';
    foreach ($font_families_intersect as $family) {
      $class_name = $this->getFontFamilyClass($family);
      $icons_html .= '<i class="' . $class_name . '" title="' . $family . '">:icon</i>';
    }

      return new FormattableMarkup('<div class="mi-result">' . $icons_html . '<span>:icon</span></div>', [
      ':icon' => $icon,
    ]);
  }

}
