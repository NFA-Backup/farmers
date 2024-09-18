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
    foreach ($collection as $name => $route) {
      // Alter JSON:API routes to check for 'access jsonapi routes' permission.
      $defaults = $route->getDefaults();
      if (!empty($defaults['_is_jsonapi']) && !empty($defaults['resource_type'])) {
        $route->setRequirement('_permission', 'access jsonapi routes');

        // Remove DELETE and POST methods from JSON:API routes.
        $methods = $route->getMethods();
        if (in_array('DELETE', $methods) || in_array('POST', $methods)) {
          // We never want to delete or post data.
          $collection->remove($name);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after any alterRoutes().
    $events[RoutingEvents::ALTER][] = ['onAlterRoutes', -600];

    return $events;
  }

}
