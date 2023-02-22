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
 * Provides a 'NFAMIS' Block.
 *
 * @Block(
 *   id = "nfafmis_block",
 *   admin_label = @Translation("NFAFMIS block"),
 *   category = @Translation("NFAFMIS"),
 * )
 */
class NfafmisBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $options = [];
    $items = [];
    if ($farmer_name) {
      $options = [
        'query' => ['title' => $farmer_name],
      ];
    }

    $current_path = $this->currentPathStack->getPath();
    $farmer_menu_items = [
      ['uri' => 'internal:/tree-farmer-overview', 'label' => 'Farmer Log'],
      ['uri' => 'internal:/tree-farmer-overview/details', 'label' => 'Farmer Details'],
      ['uri' => 'internal:/tree-farmer-overview/licences', 'label' => 'Offers/Licences'],
      ['uri' => 'internal:/tree-farmer-overview/sub-areas', 'label' => 'Sub-areas'],
      ['uri' => 'internal:/tree-farmer-overview/inventory', 'label' => 'Inventory'],
      ['uri' => 'internal:/tree-farmer-overview/harvest', 'label' => 'Harvest'],
      ['uri' => 'internal:/tree-farmer-overview/accounts', 'label' => 'Accounts'],
    ];
    foreach ($farmer_menu_items as $value) {
      // Set active class for the link.
      $path_part = explode(":", $value['uri']);
      if ($path_part[1] === $current_path) {
        $options['attributes'] = [
          'class' => [
            'tabs-item active',
          ],
        ];
      }
      else {
        $options['attributes'] = [
          'class' => [
            'tabs-item',
          ],
        ];
      }

      $url = Url::fromUri($value['uri'], $options);
      $link = Link::fromTextAndUrl($value['label'], $url);
      $items[] = $link;
    }

    $build['item_list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
      '#attributes' => ['class' => 'nav nav-tabs farmer-tabs'],
    ];
    // Set cache contexts on query_args.
    $build['#cache']['contexts'][] = 'url.query_args:title';
    $build['#cache']['contexts'][] = 'url.path';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Hide block if no farmer data available.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', 'farmer_details')
      ->condition('status', '1')
      ->accessCheck()
      ->execute();
    if (empty($nids)) {
      return AccessResult::forbidden()->addCacheableDependency($nids);
    }
    return AccessResult::allowed();
  }

}
