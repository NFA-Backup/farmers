<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\node\NodeInterface;
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
   * Farmer id.
   *
   * @var int
   */
  protected $farmer_id;

  /**
   * Show subareas on map.
   *
   * @var bool
   */
  protected $show_subareas;

  /**
   * Constructor of FarmerMapBlock.
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

    // @todo we need to refactor the farmer pages to use the farmer nid instead
    // of the title query parameter in the url
    $farmer_name = $this->requestStack->getCurrentRequest()->query->get('title');
    if ($farmer_name) {
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'farmer_details')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('title', $farmer_name)
        ->accessCheck()
        ->execute();

      // @todo if we have more than one farmer with the same name we will get
      // multiple results. Use the first one for now. When we refactor the
      // farmer views to use the node id instead of title this will be resolved.
      $this->farmer_id = reset($nids);
      $this->show_subareas = !empty($configuration['subareas']);
    }
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
    if ($this->farmer_id) {
      $layers = [];

      if ($this->show_subareas) {
        $storage = $this->entityTypeManager->getStorage('node');
        $query = $storage->getQuery()
          ->accessCheck()
          ->condition('type', 'sub_area')
          ->condition('status', NodeInterface::PUBLISHED)
          ->exists('field_map')
          ->condition('field_areas_id.entity:node.field_farmer_name_ref.entity:node', $this->farmer_id);

        $sub_areas_nids = $query->execute();
        if (!empty($sub_areas_nids)) {
          foreach ($sub_areas_nids as $sub_area_nid) {
            $layers[] = [
              'url' => '/tree-farmer-overview/subarea/geojson/' . $sub_area_nid,
              'color' => 'orange',
              'title' => $storage->load($sub_area_nid)->label(),
              'group' => $this->t('Sub areas'),
            ];
          }
        }
      }

      $layers = array_merge($layers, [
        [
          'url' => '/tree-farmer-overview/block/geojson?id=' . $this->farmer_id,
          'color' => 'yellow',
          'title' => $this->t('Area/Block'),
        ],
        [
          'url' => '/tree-farmer-overview/cfr/geojson?id=' . $this->farmer_id,
          'color' => 'red',
          'title' => $this->t('CFR'),
        ],
        [
          'url' => '/tree-farmer-overview/sector/geojson?id=' . $this->farmer_id,
          'color' => 'green',
          'title' => $this->t('Sector'),
        ],
      ]);

      $build['item_list'] = [
        '#type' => 'farm_map',
        '#map_settings' => [
          'urls' => $layers,
          'title' => $this->t('Farmer land allocations'),
          'geojson' => TRUE,
          'popup' => TRUE,
        ],
      ];

      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.query_args:title', 'url.path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [
      'node_list:sub_area',
      'taxonomy_term_list:central_forest_reserve',
      'taxonomy_term_list:block',
    ];  }

}
