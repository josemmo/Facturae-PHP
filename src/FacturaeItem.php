<?php

namespace josemmo\Facturae;

/**
 * Facturae Item
 *
 * Represents an invoice item
 */
class FacturaeItem {
  private $name = NULL;
  private $description = NULL;
  private $quantity = 1;
  private $unitPrice = NULL;
  private $unitPriceWithoutTax = NULL;
  private $taxes = array();

  private $totalAmountWithoutTax = NULL;   // $quantity * $unitPriceWithoutTax
  private $grossAmount = NULL; // For now, $grossAmount = $totalAmountWithoutTax
  private $taxAmount = NULL;
  private $totalAmount = NULL; // $totalAmountWithoutTax + $taxAmount


  /**
   * Construct
   * @param array $properties Item properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;

    // Normalize taxes array
    foreach ($this->taxes as $r=>$tax) {
      if (!is_array($tax)) $this->taxes[$r] = array("rate"=>$tax, "amount"=>0);
    }

    // Calculate unit and total amount without tax
    if (is_null($this->unitPriceWithoutTax)) {
      $percent = 1;
      if (isset($this->taxes[Facturae::TAX_IVA])) $percent +=
        $this->taxes[Facturae::TAX_IVA]['rate'] / 100;
      if (isset($this->taxes[Facturae::TAX_IRPF])) $percent -=
        $this->taxes[Facturae::TAX_IRPF]['rate'] / 100;
      $this->unitPriceWithoutTax = $this->unitPrice / $percent;
    }
    $this->totalAmountWithoutTax = $this->unitPriceWithoutTax * $this->quantity;

    // Calculate tax amount
    $this->taxAmount = 0;
    foreach ($this->taxes as $type=>$tax) {
      $this->taxes[$type]['amount'] = $this->totalAmountWithoutTax *
        ($tax['rate'] / 100);
      $this->taxAmount += $this->taxes[$type]['amount'];
    }

    // Calculate rest of values
    $this->grossAmount = $this->totalAmountWithoutTax;
    $this->totalAmount = $this->totalAmountWithoutTax + $this->taxAmount;
  }


  /**
   * Get data
   *
   * @return array Item data
   */
  public function getData() {
    return get_object_vars($this);
  }

}
