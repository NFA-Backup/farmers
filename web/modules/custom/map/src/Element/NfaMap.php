<?php

namespace Drupal\nfa_map\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\nfa_map\Event\MapRenderEvent;

/**
 * Provides a nfa_map render element.
 *
 * @RenderElement("nfa_map")
 */
class NfaMap extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderMap'],
      ],
      '#theme' => 'nfa_map',
      '#map_type' => 'default',
    ];
  }

  /**
   * Pre-render callback for the map render array.
   *
   * @param array $element
   *   A renderable array containing a #map_type property, which will be
   *   appended to 'nfa-map-' as the map element ID.
   *
   * @return array
   *   A renderable array representing the map.
   */
  public static function preRenderMap(array $element) {

    // Set the id to the map name.
    $map_id = Html::getUniqueId('nfa-map-' . $element['#map_type']);
    $element['#attributes']['id'] = $map_id;

    // Get the map type.
    /** @var \Drupal\nfa_map\Entity\MapTypeInterface $map */
    $map = \Drupal::entityTypeManager()->getStorage('map_type')->load($element['#map_type']);

    // Add the nfa-map class.
    $element['#attributes']['class'][] = 'nfa-map';

    // Attach the nfa-map and nfa_map libraries.
    $element['#attached']['library'][] = 'nfa_map/nfa-map';
    $element['#attached']['library'][] = 'nfa_map/nfa_map';

    // Include map settings.
    $map_settings = !empty($element['#map_settings']) ? $element['#map_settings'] : [];

    // Include the map options.
    $map_options = $map->getMapOptions();

    // Add the instance settings under the map id key.
    $instance_settings = array_merge_recursive($map_settings, $map_options);
    $element['#attached']['drupalSettings']['nfa_map'][$map_id] = $instance_settings;

    // Create and dispatch a MapRenderEvent.
    $event = new MapRenderEvent($map, $element);
    \Drupal::service('event_dispatcher')->dispatch(MapRenderEvent::EVENT_NAME, $event);

    // Return the element.
    return $event->element;
  }

}
