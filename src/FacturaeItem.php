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
  private $discounts = array();
  private $charges = array();
  private $taxesOutputs = array();
  private $taxesWithheld = array();

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
      // Get taxes effective percent
      $taxesPercent = 1;
      foreach (['taxesOutputs', 'taxesWithheld'] as $i=>$taxesGroupTag) {
        foreach ($this->{$taxesGroupTag} as $taxData) {
          $rate = $taxData['rate'] / 100;
          if ($i == 1) $rate *= -1; // In case of $taxesWithheld (2nd iteration)
          $taxesPercent += $rate;
        }
      }

      // Adjust discounts and charges according to taxes
      foreach (['discounts', 'charges'] as $i=>$groupTag) {
        foreach ($this->{$groupTag} as &$group) {
          if (isset($group['rate'])) continue;
          $hasTaxes = isset($group['hasTaxes']) ? $group['hasTaxes'] : true;
          if ($hasTaxes) $group['amount'] /= $taxesPercent;
        }
      }

      // Apply taxes
      $this->unitPriceWithoutTax = $this->unitPrice / $taxesPercent;
    }
  }


  /**
   * Get data for this item fixing decimals to match invoice settings
   *
   * @param  Facturae $fac Invoice instance
   * @return array         Item data
   */
  public function getData($fac) {
    $addProps = [
      'taxesOutputs' => [],
      'taxesWithheld' => [],
      'discounts' => [],
      'charges' => []
    ];

    $quantity = $fac->pad($this->quantity, 'Item/Quantity');
    $unitPriceWithoutTax = $fac->pad($this->unitPriceWithoutTax, 'Item/UnitPriceWithoutTax');
    $totalAmountWithoutTax = $fac->pad($quantity * $unitPriceWithoutTax, 'Item/TotalAmountWithoutTax');
    $grossAmount = $totalAmountWithoutTax;

    // Process charges and discounts
    foreach (['discounts', 'charges'] as $i=>$groupTag) {
      $factor = ($i == 0) ? -1 : 1;
      foreach ($this->{$groupTag} as $group) {
        if (isset($group['rate'])) {
          $rate = $fac->pad($group['rate'], 'Discount/Rate');
          $amount = $totalAmountWithoutTax * ($rate / 100);
        } else {
          $rate = null;
          $amount = $group['amount'];
        }
        $amount = $fac->pad($amount, 'Discount/Amount');
        $addProps[$groupTag][] = array(
          "reason" => $group['reason'],
          "rate" => $rate,
          "amount" => $amount
        );
        $grossAmount += $amount * $factor;
      }
    }

    // Get taxes
    $totalTaxesOutputs = 0;
    $totalTaxesWithheld = 0;
    foreach (['taxesOutputs', 'taxesWithheld'] as $i=>$taxesGroup) {
      foreach ($this->{$taxesGroup} as $type=>$tax) {
        $taxRate = $fac->pad($tax['rate'], 'Tax/Rate');
        $taxAmount = $grossAmount * ($taxRate / 100);
        $taxAmount = $fac->pad($taxAmount, 'Tax/Amount');
        $addProps[$taxesGroup][$type] = array(
          "base" => $fac->pad($grossAmount, 'Tax/Base'),
          "rate" => $taxRate,
          "amount" => $taxAmount
        );
        if ($i == 1) { // In case of $taxesWithheld (2nd iteration)
          $totalTaxesWithheld += $taxAmount;
        } else {
          $totalTaxesOutputs += $taxAmount;
        }
      }
    }

    // Add rest of properties
    $addProps['quantity'] = $quantity;
    $addProps['unitPriceWithoutTax'] = $unitPriceWithoutTax;
    $addProps['totalAmountWithoutTax'] = $totalAmountWithoutTax;
    $addProps['grossAmount'] = $fac->pad($grossAmount, 'Item/GrossAmount');
    $addProps['totalTaxesOutputs'] = $totalTaxesOutputs;
    $addProps['totalTaxesWithheld'] = $totalTaxesWithheld;
    return array_merge(get_object_vars($this), $addProps);
  }

}
