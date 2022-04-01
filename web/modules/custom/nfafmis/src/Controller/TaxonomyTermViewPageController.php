<?php

namespace Drupal\nfafmis\Controller;

use Drupal\views\Routing\ViewPageController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Determines the view to use on a taxonomy term.
 */
class TaxonomyTermViewPageController extends ViewPageController {

  /**
   * {@inheritdoc}
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match) {
    // Initialise the default configuration.
    $view_id = 'taxonomy_term';
    $display_id = 'page_1';

    // Get the vid (vocabulary machine name) of the current term.
    $term = $route_match->getParameter('taxonomy_term');
    $vid = $term->get('vid')->first()->getValue();
    $vid = $vid['target_id'];

    if ($vid == 'central_forest_reserve') {
      $view_id = 'cfr_taxonomy_term';
    }
    if ($vid == 'block') {
      $view_id = 'block_taxonomy_term';
    }
    return parent::handle($view_id, $display_id, $route_match);
  }

}
