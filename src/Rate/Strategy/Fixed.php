<?php
namespace Starbug\ShippingMethods\Rate\Strategy;

use Starbug\ShippingMethods\Rate\StrategyInterface;

class Fixed implements StrategyInterface {
  public function getRate($method, $packages) {
    return $method["price"] ?? false;
  }
}
