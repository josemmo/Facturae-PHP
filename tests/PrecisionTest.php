<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;

final class PrecisionTest extends AbstractTest {
  private function _runTest($schema, $precision) {
    $fac = $this->getBaseInvoice($schema);
    $fac->setPrecision($precision);

    // Add items
    $amounts = [37.76, 26.8, 5.5];
    foreach ($amounts as $i=>$amount) {
      $fac->addItem(new FacturaeItem([
        "name" => "Línea de producto #$i",
        "quantity" => 1,
        "unitPriceWithoutTax" => $amount,
        "taxes" => [Facturae::TAX_IVA => 21]
      ]));
    }

    // Generate invoice
    $invoiceXml = new \SimpleXMLElement($fac->export());
    $invoiceXml = $invoiceXml->Invoices->Invoice[0];

    // Validate <InvoiceTotals />
    $beforeTaxes = floatval($invoiceXml->InvoiceTotals->TotalGrossAmountBeforeTaxes);
    $taxOutputs = floatval($invoiceXml->InvoiceTotals->TotalTaxOutputs);
    $taxesWithheld = floatval($invoiceXml->InvoiceTotals->TotalTaxesWithheld);
    $invoiceTotal = floatval($invoiceXml->InvoiceTotals->InvoiceTotal);
    $actualTotal = floatval($beforeTaxes + $taxOutputs - $taxesWithheld);
    $this->assertEquals($actualTotal, $invoiceTotal, 'Incorrect invoice totals element', 0.000000001);

    // Validate total invoice amount
    if ($precision === Facturae::PRECISION_INVOICE) {
      $expectedTotal = round(array_sum($amounts)*1.21, 2);
    } else {
      $expectedTotal = array_sum(array_map(function($amount) {
        return round($amount*1.21, 2);
      }, $amounts));
    }
    $this->assertEquals($expectedTotal, $invoiceTotal, 'Incorrect total invoice amount', 0.000000001);
  }


  /**
   * Test line precision
   */
  public function testLinePrecision() {
    foreach ([Facturae::SCHEMA_3_2, Facturae::SCHEMA_3_2_1] as $schema) {
      $this->_runTest($schema, Facturae::PRECISION_LINE);
    }
  }


  /**
   * Test invoice precision
   */
  public function testInvoicePrecision() {
    foreach ([Facturae::SCHEMA_3_2, Facturae::SCHEMA_3_2_1] as $schema) {
      $this->_runTest($schema, Facturae::PRECISION_INVOICE);
    }
  }
}
