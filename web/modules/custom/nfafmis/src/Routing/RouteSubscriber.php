<?php

namespace Drupal\nfafmis\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Add a controller that sets the taxonomy term view per vocabulary.
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
      $route->setDefault('_controller', '\Drupal\nfafmis\Controller\TaxonomyTermViewPageController::handle');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after any alterRoutes().
    $events[RoutingEvents::ALTER][] = ['onAlterRoutes', -600];

    return $events;
  }

}
