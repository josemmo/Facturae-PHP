<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;

final class PrecisionTest extends AbstractTest {
  /**
   * @param string $schema    Invoice schema
   * @param string $precision Rounding precision mode
   */
  private function runTestWithParams($schema, $precision) {
    $fac = $this->getBaseInvoice($schema);
    $fac->setPrecision($precision);

    // Add items
    $items = [
      ['unitPriceWithoutTax'=>16.90, 'quantity'=>3.40,  'tax'=>10],
      ['unitPriceWithoutTax'=>5.90,  'quantity'=>1.20,  'tax'=>10],
      ['unitPriceWithoutTax'=>8.90,  'quantity'=>1.00,  'tax'=>10],
      ['unitPriceWithoutTax'=>8.90,  'quantity'=>1.75,  'tax'=>10],
      ['unitPriceWithoutTax'=>6.90,  'quantity'=>2.65,  'tax'=>10],
      ['unitPriceWithoutTax'=>5.90,  'quantity'=>1.80,  'tax'=>10],
      ['unitPriceWithoutTax'=>8.90,  'quantity'=>1.95,  'tax'=>10],
      ['unitPriceWithoutTax'=>3.00,  'quantity'=>11.30, 'tax'=>10],
      ['unitPriceWithoutTax'=>5.90,  'quantity'=>46.13, 'tax'=>10],
      ['unitPriceWithoutTax'=>37.76, 'quantity'=>1,     'tax'=>21],
      ['unitPriceWithoutTax'=>13.40, 'quantity'=>2,     'tax'=>21],
      ['unitPriceWithoutTax'=>5.50,  'quantity'=>1,     'tax'=>21]
    ];
    foreach ($items as $i=>$item) {
      $fac->addItem(new FacturaeItem([
        "name" => "Línea de producto #$i",
        "unitPriceWithoutTax" => $item['unitPriceWithoutTax'],
        "quantity" => $item['quantity'],
        "taxes" => [
          Facturae::TAX_IVA => $item['tax']
        ]
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
    $this->assertEqualsWithDelta($actualTotal, $invoiceTotal, 0.000000001, 'Incorrect invoice totals element');

    // Calculate expected invoice totals
    $expectedTotal = 0;
    $expectedTaxes = [];
    $decimals = ($precision === Facturae::PRECISION_INVOICE) ? 15 : 2;
    foreach ($items as $item) {
      if (!isset($expectedTaxes[$item['tax']])) {
        $expectedTaxes[$item['tax']] = [
          "base" => 0,
          "amount" => 0
        ];
      }
      $taxableBase = round($item['unitPriceWithoutTax'] * $item['quantity'], $decimals);
      $taxAmount = round($taxableBase * ($item['tax']/100), $decimals);
      $expectedTotal += $taxableBase + $taxAmount;
      $expectedTaxes[$item['tax']]['base'] += $taxableBase;
      $expectedTaxes[$item['tax']]['amount'] += $taxAmount;
    }
    foreach ($expectedTaxes as $key=>$value) {
      $expectedTaxes[$key]['base'] = round($value['base'], 2);
      $expectedTaxes[$key]['amount'] = round($value['amount'], 2);
    }
    $expectedTotal = round($expectedTotal, 2);

    // Validate invoice total
    // NOTE: When in invoice precision mode, we use a 1 cent tolerance as this mode prioritizes accurate invoice total
    // over invoice lines totals. This is the maximum tolerance allowed by the FacturaE specification.
    $tolerance = ($precision === Facturae::PRECISION_INVOICE) ? 0.01 : 0.000000001;
    $this->assertEqualsWithDelta($expectedTotal, $invoiceTotal, $tolerance, 'Incorrect total invoice amount');

    // Validate tax totals
    foreach ($invoiceXml->TaxesOutputs->Tax as $taxNode) {
      $rate = (float) $taxNode->TaxRate;
      $actualBase = (float) $taxNode->TaxableBase->TotalAmount;
      $actualAmount = (float) $taxNode->TaxAmount->TotalAmount;
      $expectedBase = $expectedTaxes[$rate]['base'];
      $expectedAmount = $expectedTaxes[$rate]['amount'];
      $this->assertEqualsWithDelta($expectedBase, $actualBase, 0.000000001, "Incorrect taxable base for $rate% rate");
      $this->assertEqualsWithDelta($expectedAmount, $actualAmount, 0.000000001, "Incorrect tax amount for $rate% rate");
    }
  }


  /**
   * Test line precision
   */
  public function testLinePrecision() {
    foreach ([Facturae::SCHEMA_3_2, Facturae::SCHEMA_3_2_1] as $schema) {
      $this->runTestWithParams($schema, Facturae::PRECISION_LINE);
    }
  }


  /**
   * Test invoice precision
   */
  public function testInvoicePrecision() {
    foreach ([Facturae::SCHEMA_3_2_1] as $schema) {
      $this->runTestWithParams($schema, Facturae::PRECISION_INVOICE);
    }
  }
}
