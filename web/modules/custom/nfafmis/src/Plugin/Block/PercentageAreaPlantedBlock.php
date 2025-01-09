<?php

namespace Drupal\nfafmis\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Percentage Area Planted' Block.
 *
 * @Block(
 *   id = "percentage_area_planted_block",
 *   admin_label = @Translation("Percentage area planted block"),
 *   category = @Translation("NFA Farmers"),
 * )
 */
class PercentageAreaPlantedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PercentageAreaPlantedBlock object.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $total_area = $this->getTotalArea();
    $total_planted_area = $this->getTotalPlantedArea();

    if ($total_area > 0) {
      $percentage_planted = ($total_planted_area / $total_area) * 100;
    }
    else {
      $percentage_planted = 0;
    }

    return [
      '#markup' => $this->t('@percentage%', ['@percentage' => number_format($percentage_planted, 2)]),
    ];
  }

  /**
   * Get the total overall area from offer_license content type.
   */
  private function getTotalArea() {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();

    $query->condition('type', 'offer_license')
      ->condition('status', 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $sum = 0;

    if (!empty($nids)) {
      // Load the nodes.
      $nodes = $storage->loadMultiple($nids);

      // Sum the field_overall_area values.
      foreach ($nodes as $node) {
        if ($node->hasField('field_overall_area') && !$node->get('field_overall_area')->isEmpty()) {
          $sum += $node->get('field_overall_area')->value;
        }
      }
    }

    return $sum;
  }

  /**
   * Get the total subarea planted from sub_area content type.
   */
  private function getTotalPlantedArea() {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();

    $query->condition('type', 'sub_area')
      ->condition('status', 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $sum = 0;

    if (!empty($nids)) {
      // Load the nodes.
      $nodes = $storage->loadMultiple($nids);

      // Sum the field_overall_area values.
      foreach ($nodes as $node) {
        if ($node->hasField('field_subarea_planted') && !$node->get('field_subarea_planted')->isEmpty()) {
          $sum += $node->get('field_subarea_planted')->value;
        }
      }
    }

    return $sum;
  }

}
