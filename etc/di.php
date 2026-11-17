<?php
namespace Starbug\ShippingMethods;

use Starbug\ShippingMethods\Rate\Strategy\Fixed;
use Starbug\ShippingMethods\Rate\StrategyFactory;
use Starbug\ShippingMethods\Rate\StrategyFactoryInterface;
use Starbug\ShippingMethods\Rate\StrategyInterface;

use function DI\add;
use function DI\autowire;
use function DI\get;

return [
  "shipping_methods.strategies" => add([
    "fixed" => [
      "name" => "Fixed Rate",
      "class" => Fixed::class,
      "settings" => [
        [
          "name" => "price",
          "label" => "Price",
          "input_type" => "text",
          "data-dojo-type" => "sb/form/Currency"
        ]
      ]
    ]
  ]),
  "db.schema.migrations" => add([
    get(Migration::class)
  ]),
  StrategyFactoryInterface::class => autowire(StrategyFactory::class),
  StrategyInterface::class => autowire(Fixed::class)
];
