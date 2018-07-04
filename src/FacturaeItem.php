<?php
namespace josemmo\Facturae;

/**
 * Facturae Item
 *
 * Represents an invoice item
 */
class FacturaeItem {

  private $articleCode = null;
  private $name = null;
  private $description = null;
  private $quantity = 1;
  private $unitOfMeasure = Facturae::UNIT_DEFAULT;
  private $unitPrice = null;
  private $unitPriceWithoutTax = null;
  private $taxesOutputs = array();
  private $taxesWithheld = array();

  private $totalAmountWithoutTax = null; // $quantity * $unitPriceWithoutTax
  private $grossAmount = null; // For now, $grossAmount = $totalAmountWithoutTax
  private $totalTaxesOutputs = null;
  private $totalTaxesWithheld = null;
  private $totalAmount = null; // $totalAmountWithoutTax + $totalTaxesOutputs - $totalTaxesWithheld

  private $issuerContractReference = null;
  private $issuerContractDate = null;
  private $issuerTransactionReference = null;
  private $issuerTransactionDate = null;
  private $receiverContractReference = null;
  private $receiverContractDate = null;
  private $receiverTransactionReference = null;
  private $receiverTransactionDate = null;
  private $fileReference = null;
  private $fileDate = null;
  private $sequenceNumber = null;


  /**
   * Construct
   *
   * @param array $properties Item properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) {
      if ($key == "taxes") continue; // Ignore taxes property, we'll deal with it later
      $this->{$key} = $value;
    }

    // Catalog taxes property (backward compatibility)
    if (isset($properties['taxes'])) {
      foreach ($properties['taxes'] as $r=>$tax) {
        if (!is_array($tax)) $tax = array("rate"=>$tax, "amount"=>0);
        if (!isset($tax['isWithheld'])) { // Get value by default
          $tax['isWithheld'] = Facturae::isWithheldTax($r);
        }
        if ($tax['isWithheld']) {
          $this->taxesWithheld[$r] = $tax;
        } else {
          $this->taxesOutputs[$r] = $tax;
        }
      }
    }

    // Calculate unit and total amount without tax
    if (is_null($this->unitPriceWithoutTax)) {
      $percent = 1;
      foreach ([$this->taxesOutputs, $this->taxesWithheld] as $i=>$taxesGroup) {
        foreach ($taxesGroup as $type=>$taxData) {
          $rate = $taxData['rate'] / 100;
          if ($i == 1) $rate *= -1; // In case of $taxesWithheld (2nd iteration)
          $percent += $rate;
        }
      }
      $this->unitPriceWithoutTax = $this->unitPrice / $percent;
    }
    $this->totalAmountWithoutTax = $this->unitPriceWithoutTax * $this->quantity;

    // Calculate tax amount
    $this->totalTaxesOutputs = 0;
    $this->totalTaxesWithheld = 0;
    foreach (["taxesOutputs", "taxesWithheld"] as $i=>$taxesGroup) {
      foreach ($this->{$taxesGroup} as $type=>$tax) {
        $this->{$taxesGroup}[$type]['amount'] = $this->totalAmountWithoutTax *
          ($tax['rate'] / 100);
        if ($i == 1) { // In case of $taxesWithheld (2nd iteration)
          $this->totalTaxesWithheld += $this->{$taxesGroup}[$type]['amount'];
        } else {
          $this->totalTaxesOutputs += $this->{$taxesGroup}[$type]['amount'];
        }
      }
    }

    // Calculate rest of values
    $this->grossAmount = $this->totalAmountWithoutTax;
    $this->totalAmount = $this->totalAmountWithoutTax +
      $this->totalTaxesOutputs - $this->totalTaxesWithheld;
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
