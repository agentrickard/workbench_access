<?php

namespace Drupal\workbench_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Class TaxonomyChecksRouteSubscriber.
 *
 * @package Drupal\anl_taxonomy_checks\Routing
 */
class TaxonomyChecksRouteSubscriber extends RouteSubscriberBase {

  private $taxonomyDeleteRoute = 'entity.taxonomy_term.delete_form';

  /**
   * Alter the taxonomy route to add our check to see if its ok to delete.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   Injected route collection.
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $collection->get($this->taxonomyDeleteRoute);

    if ($route instanceof Route) {
  //    $route->setRequirement('_taxonomy_delete_access_check', 'TRUE');
    }

  }

}
