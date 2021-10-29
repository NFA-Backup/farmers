<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Block showing the farmer's subareas on a map.
 *
 * @Block(
 *   id = "farmer_map_block",
 *   admin_label = @Translation("Farmer map block"),
 *   category = @Translation("NFAFMIS"),
 * )
 */
class FarmerMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Request stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * An instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor of NfafmisBlock.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    CurrentPathStack $current_path_stack,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->currentPathStack = $current_path_stack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $farmer_name = $this->requestStack->getCurrentRequest()->query->get('title');

    if ($farmer_name) {
      // Check does farmer have sub area map data.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'sub_area')
        ->exists('field_map')
        ->condition('field_areas_id.entity:node.field_farmer_name_ref.entity:node.title', $farmer_name);

      $nids = $query->execute();
      if ($nids) {
        $url = '/tree-farmer-overview/geojson?title=' . $farmer_name;
        $build['item_list'] = [
          '#type' => 'farm_map',
          '#map_settings' => [
            'url' => $url,
            'title' => $this->t('@farmer sub areas', ['@farmer' => $farmer_name]),
            'geojson' => TRUE,
            'popup' => TRUE,
          ],
        ];

        // Set cache contexts on query_args.
        $build['#cache']['contexts'][] = 'url.query_args:title';
        $build['#cache']['contexts'][] = 'url.path';

        return $build;
      }
    }
  }

}
