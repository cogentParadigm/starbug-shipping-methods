<?php
namespace Starbug\ShippingMethods\Tests\Query;

use PHPUnit\Framework\TestCase;
use Starbug\ShippingMethods\Query\ShippingMethodsSelectCollection;
use Starbug\Db\DatabaseInterface;
use Starbug\Db\Schema\SchemerInterface;
use Starbug\Db\Schema\Schema;
use Starbug\ShippingMethods\Rate\Calculator;
use Starbug\Cart\Repository\ProductsRepository;
use Starbug\Price\FormatterInterface;
use Starbug\BoxPacker\ProductBoxPacker;

class ShippingMethodsSelectCollectionTest extends TestCase {

  protected function createCollection(
    $products = [],
    $packages = [],
    $rateResponse = false
  ): TestableShippingMethodsSelectCollection {
    $db = $this->createMock(DatabaseInterface::class);
    $schemer = $this->createMock(SchemerInterface::class);
    $schema = new Schema();
    $schema->addTable("shipping_methods",
      ["name", "type" => "string", "length" => "128"],
      ["strategy", "type" => "string"],
      ["minimum_weight", "type" => "string", "null" => true],
      ["maximum_weight", "type" => "string", "null" => true]
    );
    $schemer->method('getSchema')->willReturn($schema);

    $rates = $this->createMock(Calculator::class);
    $rates->method('getRate')->willReturn($rateResponse);

    $productsRepo = $this->createMock(ProductsRepository::class);
    $productsRepo->method('getProducts')->willReturn($products);

    $price = $this->createMock(FormatterInterface::class);
    $price->method('format')->willReturnCallback(function ($v) {
      return '$' . ($v / 100);
    });

    $packer = $this->createMock(ProductBoxPacker::class);
    $packer->method('getPackages')->willReturn($packages);

    $collection = new TestableShippingMethodsSelectCollection($db, $schemer, $rates, $productsRepo, $price, $packer);
    return $collection;
  }

  public function testEmptyRowsReturnsEmpty() {
    $collection = $this->createCollection();
    $this->assertSame([], $collection->filterRows([]));
  }

  public function testEmptyProductsReturnsEmpty() {
    $collection = $this->createCollection([]);
    $rows = [["id" => 1, "name" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => null]];
    $this->assertSame([], $collection->filterRows($rows));
  }

  public function testMethodExcludedByMinimumWeight() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 1, "width" => 10, "length" => 10, "height" => 10]];
    $packages = [["weight" => 100, "width" => 10, "length" => 10, "height" => 10]];
    $collection = $this->createCollection($products, $packages, ["price" => 1500, "transit" => 3]);

    $rows = [["id" => 1, "name" => "UPS Ground", "strategy" => "ups", "minimum_weight" => "200", "maximum_weight" => null]];
    $this->assertSame([], $collection->filterRows($rows));
  }

  public function testMethodExcludedByMaximumWeightIndividualItem() {
    $products = [["sku" => "A", "weight" => 2000, "qty" => 1, "width" => 10, "length" => 10, "height" => 10]];
    $packages = [["weight" => 2000, "width" => 10, "length" => 10, "height" => 10]];
    $collection = $this->createCollection($products, $packages, ["price" => 1500, "transit" => 3]);

    $rows = [["id" => 1, "name" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "1500"]];
    $this->assertSame([], $collection->filterRows($rows));
  }

  public function testMethodExcludedByPostPackMaxBoxWeight() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 2, "width" => 10, "length" => 10, "height" => 10]];
    // Packer returns a single box of weight 200, exceeding the 150 per-box limit
    $packages = [["weight" => 200, "width" => 10, "length" => 10, "height" => 10]];
    $collection = $this->createCollection($products, $packages, ["price" => 1500, "transit" => 3]);

    $rows = [["id" => 1, "name" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "150"]];
    $this->assertSame([], $collection->filterRows($rows));
  }

  public function testValidMethodReturnsResults() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 1, "width" => 10, "length" => 10, "height" => 10]];
    $packages = [["weight" => 100, "width" => 10, "length" => 10, "height" => 10]];
    $collection = $this->createCollection($products, $packages, ["price" => 1500, "transit" => 3]);

    $rows = [["id" => 1, "name" => "UPS Ground", "label" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "200"]];
    $results = $collection->filterRows($rows);
    $this->assertCount(1, $results);
    $this->assertSame(1500, $results[0]["price"]);
  }

  public function testMultipleMethodsWithDifferentLimits() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 2, "width" => 10, "length" => 10, "height" => 10]];
    // Method 1: max 150, packer returns box 200 -> excluded post-pack
    // Method 2: max 250, packer returns box 200 -> included
    $packagesHeavy = [["weight" => 200, "width" => 10, "length" => 10, "height" => 10]];
    $packagesLight = [["weight" => 200, "width" => 10, "length" => 10, "height" => 10]];

    $db = $this->createMock(DatabaseInterface::class);
    $schemer = $this->createMock(SchemerInterface::class);
    $schema = new Schema();
    $schema->addTable("shipping_methods",
      ["name", "type" => "string", "length" => "128"],
      ["strategy", "type" => "string"],
      ["minimum_weight", "type" => "string", "null" => true],
      ["maximum_weight", "type" => "string", "null" => true]
    );
    $schemer->method('getSchema')->willReturn($schema);

    $rates = $this->createMock(Calculator::class);
    $rates->method('getRate')->willReturn(["price" => 1500, "transit" => 3]);

    $productsRepo = $this->createMock(ProductsRepository::class);
    $productsRepo->method('getProducts')->willReturn($products);

    $price = $this->createMock(FormatterInterface::class);
    $price->method('format')->willReturnCallback(function ($v) {
      return '$' . ($v / 100);
    });

    $packer = $this->createMock(ProductBoxPacker::class);
    // Map maxBoxWeight to different packages
    $packer->method('getPackages')->willReturnCallback(function ($products, $selfBoxed, $maxBoxWeight) {
      if ($maxBoxWeight === 150.0) {
        return [["weight" => 200, "width" => 10, "length" => 10, "height" => 10]];
      }
      return [["weight" => 200, "width" => 10, "length" => 10, "height" => 10]];
    });

    $collection = new TestableShippingMethodsSelectCollection($db, $schemer, $rates, $productsRepo, $price, $packer);

    $rows = [
      ["id" => 1, "name" => "UPS Standard", "label" => "UPS Standard", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "150"],
      ["id" => 2, "name" => "UPS Heavy", "label" => "UPS Heavy", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "250"]
    ];
    $results = $collection->filterRows($rows);
    $this->assertCount(1, $results);
    $this->assertSame("UPS Heavy", $results[0]["name"]);
  }

  public function testEmptyPackagesSkipsMethod() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 1, "width" => 10, "length" => 10, "height" => 10]];
    $collection = $this->createCollection($products, []);

    $rows = [["id" => 1, "name" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => null]];
    $this->assertSame([], $collection->filterRows($rows));
  }

  public function testPackerCalledWithMaxBoxWeight() {
    $products = [["sku" => "A", "weight" => 100, "qty" => 1, "width" => 10, "length" => 10, "height" => 10]];
    $packages = [["weight" => 100, "width" => 10, "length" => 10, "height" => 10]];

    $packer = $this->createMock(ProductBoxPacker::class);
    $packer->expects($this->once())
      ->method('getPackages')
      ->with($products, [], 150.0)
      ->willReturn($packages);

    $db = $this->createMock(DatabaseInterface::class);
    $schemer = $this->createMock(SchemerInterface::class);
    $schema = new Schema();
    $schema->addTable("shipping_methods",
      ["name", "type" => "string", "length" => "128"],
      ["strategy", "type" => "string"],
      ["minimum_weight", "type" => "string", "null" => true],
      ["maximum_weight", "type" => "string", "null" => true]
    );
    $schemer->method('getSchema')->willReturn($schema);

    $rates = $this->createMock(Calculator::class);
    $rates->method('getRate')->willReturn(["price" => 1500, "transit" => 3]);

    $productsRepo = $this->createMock(ProductsRepository::class);
    $productsRepo->method('getProducts')->willReturn($products);

    $price = $this->createMock(FormatterInterface::class);

    $collection = new TestableShippingMethodsSelectCollection($db, $schemer, $rates, $productsRepo, $price, $packer);

    $rows = [["id" => 1, "name" => "UPS Ground", "label" => "UPS Ground", "strategy" => "ups", "minimum_weight" => null, "maximum_weight" => "150"]];
    $results = $collection->filterRows($rows);
    $this->assertCount(1, $results);
  }
}
