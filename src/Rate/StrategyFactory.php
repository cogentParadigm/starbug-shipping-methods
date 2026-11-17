<?php
namespace Starbug\ShippingMethods\Rate;

use Exception;
use Psr\Container\ContainerInterface;

class StrategyFactory implements StrategyFactoryInterface {
  protected $strategies = [];
  public function __construct(
    protected ContainerInterface $container
  ) {
    $this->strategies = $container->get("shipping_methods.strategies");
  }
  public function get($strategy) : StrategyInterface {
    if (!isset($this->strategies[$strategy])) {
      throw new Exception(StrategyInterface::class." implementation not found: ".$strategy);
    }
    $className = $this->strategies[$strategy]["class"];
    return $this->container->get($className);
  }
  public function getStrategies(): array {
    return $this->strategies;
  }
}
