<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;

final class DecimalsTest extends AbstractTest {

  const NUM_OF_TESTS = 1000;
  const ITEMS_PER_INVOICE = 3;

  const PRICE_DECIMALS = 8;
  const QUANTITY_DECIMALS = 6;
  const TAX_DECIMALS = 6;


  /**
   * Run test on a random invoice
   * @param  string  $schema FacturaE schema
   * @return boolean         Success
   */
  private function _runTest($schema) {
    $fac = $this->getBaseInvoice($schema);

    // Add items with random values
    $pricePow = 10 ** self::PRICE_DECIMALS;
    $quantityPow = 10 ** self::QUANTITY_DECIMALS;
    $taxPow = 10 ** self::TAX_DECIMALS;
    for ($i=0; $i<self::ITEMS_PER_INVOICE; $i++) {
      $unitPrice = mt_rand(1, $pricePow) / $pricePow;
      $quantity = mt_rand(1, $quantityPow*10) / $quantityPow;
      $specialTax = mt_rand(1, $taxPow*20) / $taxPow;
      $fac->addItem(new FacturaeItem([
        "name" => "Línea de producto #$i",
        "quantity" => $quantity,
        "unitPrice" => $unitPrice,
        "taxes" => [
          Facturae::TAX_IVA   => 10,
          Facturae::TAX_IRPF  => 15,
          Facturae::TAX_OTHER => $specialTax
        ]
      ]));
    }

    // Validate invoice totals
    $invoiceXml = new \SimpleXMLElement($fac->export());
    $invoiceXml = $invoiceXml->Invoices->Invoice[0];

    $beforeTaxes = floatval($invoiceXml->InvoiceTotals->TotalGrossAmountBeforeTaxes);
    $taxOutputs = floatval($invoiceXml->InvoiceTotals->TotalTaxOutputs);
    $taxesWithheld = floatval($invoiceXml->InvoiceTotals->TotalTaxesWithheld);
    $invoiceTotal = floatval($invoiceXml->InvoiceTotals->InvoiceTotal);
    $actualTotal = floatval($beforeTaxes + $taxOutputs - $taxesWithheld);

    return (abs($invoiceTotal-$actualTotal) < 0.000000001);
  }


  /**
   * Test decimals
   */
  public function testDecimals() {
    $totalCount = 0;
    $successCount = 0;
    foreach ([Facturae::SCHEMA_3_2, Facturae::SCHEMA_3_2_1] as $schema) {
      for ($i=0; $i<self::NUM_OF_TESTS; $i++) {
        if ($this->_runTest($schema)) $successCount++;
        $totalCount++;
      }
    }
    $this->assertEquals($totalCount, $successCount);
  }

}
