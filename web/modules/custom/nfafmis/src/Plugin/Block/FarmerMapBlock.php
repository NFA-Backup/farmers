<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Cache\Cache;
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
        ->execute();

      // @todo if we have more than one farmer with the same name we will get
      // multiple results. Use the first one for now. When we refactor the
      // farmer views to use the node id instead of title this will be resolved.
      $this->farmer_id = reset($nids);
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
      // Check does farmer have sub area map data.
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'sub_area')
        ->condition('status', NodeInterface::PUBLISHED)
        ->exists('field_map')
        ->condition('field_areas_id.entity:node.field_farmer_name_ref.entity:node', $this->farmer_id);

      $sub_areas_nids = $query->count()->execute();
      if ($sub_areas_nids) {
        $url = '/tree-farmer-overview/geojson?id=' . $this->farmer_id;
        $build['item_list'] = [
          '#type' => 'farm_map',
          '#map_settings' => [
            'url' => $url,
            'title' => $this->t('Sub areas'),
            'geojson' => TRUE,
            'popup' => TRUE,
          ],
        ];

        return $build;
      }
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
    return ['node_list:sub_area'];
  }

}
