<?php
namespace Starbug\ShippingMethods\Routing;

use Starbug\Routing\RouteProviderInterface;
use Starbug\Api\Controller\CollectionController;
use Starbug\Routing\Route;
use Starbug\ShippingMethods\Query\ShippingMethodsSelectCollection;

class RouteProvider implements RouteProviderInterface {

  public function configure(Route $routes) {
    $routes->addRoute("api/shipping-methods/select.{format:json}", CollectionController::class, [
      "model" => "shipping_methods",
      "collection" => ShippingMethodsSelectCollection::class
    ]);
  }
}
