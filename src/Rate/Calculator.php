<?php
namespace Starbug\ShippingMethods\Rate;

class Calculator {
  public function __construct(
    protected StrategyFactoryInterface $strategies
  ) {
  }
  public function getRate($method, $packages) {
    $strategy = $this->getStrategyForMethod($method);
    return $strategy->getRate($method, $packages);
  }
  protected function getStrategyForMethod($method) {
    if (empty($method["strategy"])) {
      $method["strategy"] = StrategyInterface::class;
    }
    return $this->strategies->get($method["strategy"]);
  }
}
