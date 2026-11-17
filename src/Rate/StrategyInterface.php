<?php
namespace Starbug\ShippingMethods\Rate;

interface StrategyInterface {
  public function getRate($method, $packages);
}
