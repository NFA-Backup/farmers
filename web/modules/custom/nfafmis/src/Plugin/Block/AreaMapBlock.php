<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a map showing the subareas of an area.
 *
 * @Block(
 *   id = "area_map_block",
 *   admin_label = @Translation("Area map block"),
 *   category = @Translation("NFA Farmers"),
 *   context_definitions  = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class AreaMapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $area_nid = $this->configuration['area_nid'];

    $layers = [

      [
        'url' => '/area/block/geojson?id=' . $area_nid,
        'color' => 'yellow',
        'title' => $this->t('Block'),
      ],
      [
        'url' => '/area/geojson?id=' . $area_nid,
        'color' => 'orange',
        'title' => $this->t('Sub areas'),
      ],
      [
        'url' => '/area/cfr/geojson?id=' . $area_nid,
        'color' => 'grey',
        'title' => $this->t('CFR'),
      ],
    ];
    $build['item_list'] = [
      '#type' => 'farm_map',
      '#map_id' => $area_nid,
      '#map_settings' => [
        'urls' => $layers,
        'title' => $this->t('Sub areas'),
        'geojson' => TRUE,
        'popup' => TRUE,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [
      'node_list:sub_area',
      'taxonomy_term_list:central_forest_reserve',
      'taxonomy_term_list:block',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContextMapping() {
    $mapping = parent::getContextMapping();
    // By default, get the node from the URL.
    return $mapping ?: ['node' => '@node.node_route_context:node'];
  }
}
