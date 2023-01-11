<?php

namespace Drupal\nfafmis\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates referenced entities.
 */
class LandRentRateConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->bundle() != 'land_rent_rates') {
      return;
    }
    $cfr = $entity->field_central_forest_reserve->target_id;
    $year = $entity->field_rate_year->value;

    // Search for nodes with the same CFR and year, excluding the node being
    // edited.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'land_rent_rates')
      ->condition('field_central_forest_reserve.target_id', $cfr)
      ->condition('field_rate_year.value', $year)
      ->accessCheck(TRUE);

    if ($entity->id()) {
      $query->condition('nid', $entity->id(), '!=');
    }

    $nids = $query->execute();

    if (!empty($nids)) {
      $this->context->addViolation($constraint->message, ['%cfr' => $entity->field_central_forest_reserve->entity->label(), '%year' => $year ]);
    }
  }

}
