<?php

namespace Drupal\area_summary\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for area summary routes.
 */
class AreaSummaryController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
