<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'NFAMIS' Block.
 *
 * @Block(
 *   id = "nfafmis_block",
 *   admin_label = @Translation("NFAFMIS block"),
 *   category = @Translation("NFAFMIS"),
 * )
 */
class NfafmisBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $farmer_name = \Drupal::request()->query->get('title');
    $options = [];
    if ($farmer_name) {
      $options = [
        'query' => ['title' => $farmer_name]
      ];
    }

    $current_path = \Drupal::service('path.current')->getPath();
    $farmer_menu_items = [
      ['uri' => 'internal:/tree-farmer-overview', 'label' => 'Farmer Log'],
      ['uri' => 'internal:/tree-farmer-overview/details', 'label' => 'Farmer Details'],
      ['uri' => 'internal:/tree-farmer-overview/licences', 'label' => 'Offers/Licences'],
      ['uri' => 'internal:/tree-farmer-overview/sub-areas', 'label' => 'Sub-areas'],
      ['uri' => 'internal:/tree-farmer-overview/inventory', 'label' => 'Inventory'],
      ['uri' => 'internal:/tree-farmer-overview/accounts', 'label' => 'Accounts'],
      ['uri' => 'internal:/tree-farmer-overview/harvest', 'label' => 'Harvest'],
    ];
    foreach ($farmer_menu_items as $key => $value) {
      // Set active class for the link.
      $path_part = explode(":", $value['uri']);
      if ($path_part[1] === $current_path) {
        $options['attributes'] = [
          'class' => [
            'tabs-item active',
          ],
        ];
      }else{
        $options['attributes'] = [
          'class' => [
            'tabs-item',
          ],
        ];
      }

      $url = Url::fromUri($value['uri'], $options);
      $link = Link::fromTextAndUrl($this->t($value['label']), $url);
      $build[$key] = $link;
    }

    $build['item_list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $build,
      '#attributes' => ['class' => 'nav nav-tabs farmer-tabs'],
    ];

    // Set cache contexts on query_args.
    $build['#cache']['contexts'][] = 'url.query_args:title';
    $build['#cache']['contexts'][] = 'url.path';
    return $build;
  }

}
