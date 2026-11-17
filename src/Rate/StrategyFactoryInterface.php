<?php
namespace Starbug\ShippingMethods\Rate;

interface StrategyFactoryInterface {
  public function get($strategy): StrategyInterface;
  public function getStrategies(): array;
}
