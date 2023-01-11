<?php

namespace Drupal\nfafmis\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Constraint to allow only one land rent rate per CFR per year.
 *
 * @Constraint(
 *   id = "LandRentRateConstraint",
 *   label = @Translation("Constraint to allow only one land rent rate per CFR per year."),
 *   type = "entity"
 * )
 */
class LandRentRateConstraint extends CompositeConstraintBase {

  public $message = 'A land rent rate for %cfr for year %year already exists';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_central_forest_reserve', 'field_rate_year'];
  }

}
