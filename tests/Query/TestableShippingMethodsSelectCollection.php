<?php
namespace Starbug\ShippingMethods\Tests\Query;

use Starbug\ShippingMethods\Query\ShippingMethodsSelectCollection;

class TestableShippingMethodsSelectCollection extends ShippingMethodsSelectCollection {
  protected function addSettings($row) {
    return $row;
  }
  protected function addDestination($row) {
    return $row;
  }
  public function setOps($ops) {
    $this->ops = $ops;
  }
}
