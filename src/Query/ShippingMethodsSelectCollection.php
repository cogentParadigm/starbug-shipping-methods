<?php
namespace Starbug\ShippingMethods\Query;

use Starbug\BoxPacker\ProductBoxPacker;
use Starbug\Cart\Repository\ProductsRepository;
use Starbug\Db\Collection\SelectCollection;
use Starbug\Db\DatabaseInterface;
use Starbug\Db\Schema\SchemerInterface;
use Starbug\Price\FormatterInterface;
use Starbug\ShippingMethods\Rate\Calculator;

class ShippingMethodsSelectCollection extends SelectCollection {
  protected $model = "shipping_methods";
  protected $ops = [];
  public function __construct(
    DatabaseInterface $db,
    SchemerInterface $schemer,
    protected Calculator $rates,
    protected ProductsRepository $products,
    protected FormatterInterface $price,
    protected ProductBoxPacker $packer
  ) {
    parent::__construct($db, $schemer);
  }
  public function build($query, $ops) {
    $this->ops = $ops;
    $query = parent::build($query, $ops);
    $query->select(["name", "strategy", "description", "minimum_weight", "maximum_weight"], "shipping_methods");
    $query->sort("shipping_methods.position");
    return $query;
  }
  public function filterRows($rows) {
    if (empty($rows)) {
      return [];
    }
    $products = $this->products->getProducts();
    if (empty($products)) {
      return [];
    }
    $totalWeight = 0;
    $maxItemWeight = 0;
    foreach ($products as $product) {
      $qty = $product["qty"] ?? 1;
      $weight = $product["weight"] ?? 0;
      $totalWeight += $weight * $qty;
      $maxItemWeight = max($maxItemWeight, $weight);
    }
    $results = [];
    foreach ($rows as $row) {
      $method = $this->addSettings($row);
      $method = $this->addDestination($method);
      $minimumWeight = floatval($method["minimum_weight"] ?? 0);
      $maximumWeight = empty($method["maximum_weight"]) ? null : floatval($method["maximum_weight"]);
      $aboveMinimumWeight = $totalWeight >= $minimumWeight;
      $belowMaximumWeight = $maximumWeight === null || $maxItemWeight <= $maximumWeight;
      $validWeight = $aboveMinimumWeight && $belowMaximumWeight;
      if (!$validWeight) {
        continue;
      }
      $packages = $this->packer->getPackages($products, [], $maximumWeight);
      if (empty($packages)) {
        continue;
      }
      $maxBoxWeight = max(array_column($packages, "weight"));
      $postPackValid = $maximumWeight === null || $maxBoxWeight <= $maximumWeight;
      if ($postPackValid && $response = $this->rates->getRate($method, $packages)) {
        if (is_array($response)) {
          $row["price"] = $response["price"];
          $row["label"] .= " - " . $this->price->format($response["price"]) . " (estimated delivery in " . $response["transit"] . " business days)";
        } else {
          $row["price"] = $response;
          $row["label"] .= " - ".$this->price->format($response);
        }
        $results[] = $row;
      }
    }
    return $results;
  }
  protected function addSettings($row) {
    $settings = $this->db->query("shipping_methods_settings")
      ->condition("shipping_methods_id", $row["id"])
      ->all();
    foreach ($settings as $setting) {
      $row[$setting["name"]] = $setting["value"];
    }
    return $row;
  }
  protected function addDestination($row) {
    if (!empty($this->ops["shipping_address"])) {
      $row["destination"] = $this->db->query("address")
        ->leftJoin("provinces")->on("address.administrative_area=provinces.id")
        ->condition("address.id", $this->ops["shipping_address"])
        ->select("address.*,address.country.code as country")
        ->select("IFNULL(provinces.code, address.administrative_area) as administrative_area")
        ->one();
    }
    return $row;
  }
}
