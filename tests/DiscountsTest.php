<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;

final class DiscountsTest extends AbstractTest {

  /**
   * Test invoice item discounts
   */
  public function testItemDiscounts() {
    $fac = $this->getBaseInvoice();
    $expectedGrossAmounts = [];

    // Add first item
    $fac->addItem(new FacturaeItem([
      "name" => "First item",
      "unitPriceWithoutTax" => 100,
      "discounts" => [
        ["reason"=>"Half price", "rate"=>50]
      ],
      "taxes" => [Facturae::TAX_IVA => 10]
    ]));
    $expectedGrossAmounts[] = 50;

    // Add second item
    $fac->addItem(new FacturaeItem([
      "name" => "Second item",
      "unitPriceWithoutTax" => 100,
      "discounts" => [
        ["reason"=>"Half price", "rate"=>50],
        ["reason"=>"5€ off", "amount"=>5]
      ],
      "charges" => [
        ["reason"=>"Twice as much", "rate"=>50]
      ],
      "taxes" => [Facturae::TAX_IVA => 10]
    ]));
    $expectedGrossAmounts[] = 95;

    // Add third item
    $fac->addItem(new FacturaeItem([
      "name" => "Third item",
      "quantity" => 2,
      "unitPrice" => 100,
      "discounts" => [
        ["reason"=>"Half price", "rate"=>50]
      ],
      "taxes" => [Facturae::TAX_IVA => 0]
    ]));
    $expectedGrossAmounts[] = 100;

    // Add fourth item
    $fac->addItem(new FacturaeItem([
      "name" => "Fourth item",
      "unitPrice" => 100,
      "discounts" => [
        ["reason"=>"Half price", "rate"=>50]
      ],
      "charges" => [
        ["reason"=>"Extra €5 (tax. included)",  "amount"=>5],
        ["reason"=>"Extra €10", "amount"=>10, "hasTaxes"=>false],
      ],
      "taxes" => [Facturae::TAX_IVA => 25]
    ]));
    $expectedGrossAmounts[] = (100 / 1.25)*0.5 + (5 / 1.25) + 10;

    // Generate invoice and validate output
    $invoiceXml = new \SimpleXMLElement($fac->export());
    $invoiceXml = $invoiceXml->Invoices->Invoice[0];
    foreach ($invoiceXml->Items->InvoiceLine as $item) {
      $itemGross = floatval($item->GrossAmount);
      $taxableBase = floatval($item->TaxesOutputs->Tax[0]->TaxableBase->TotalAmount);
      $expectedGross = array_shift($expectedGrossAmounts);
      $this->assertEqualsWithDelta($itemGross, $expectedGross, 0.00001);
      $this->assertEqualsWithDelta($taxableBase, $expectedGross, 0.00001);
    }

    // Validate total amounts
    $totalGrossAmount = floatval($invoiceXml->InvoiceTotals->TotalGrossAmount);
    $totalTaxOutputs = floatval($invoiceXml->InvoiceTotals->TotalTaxOutputs);
    $this->assertEqualsWithDelta(299, $totalGrossAmount, 0.00001);
    $this->assertEqualsWithDelta(28, $totalTaxOutputs, 0.00001);
  }


  /**
   * Test general discounts
   */
  public function testGeneralDiscounts() {
    $fac = $this->getBaseInvoice();
    $fac->addItem('Test item', 100, 1, Facturae::TAX_IVA, 25);
    $fac->addDiscount('Half price', 50);
    $fac->addDiscount('5€ off', 5, false);
    $fac->addCharge('Twice as much', 50);

    // Generate invoice and validate output
    $invoiceXml = new \SimpleXMLElement($fac->export());
    $invoiceXml = $invoiceXml->Invoices->Invoice[0];
    $totalDiscounts = floatval($invoiceXml->InvoiceTotals->TotalGeneralDiscounts);
    $totalCharges = floatval($invoiceXml->InvoiceTotals->TotalGeneralSurcharges);
    $invoiceTotal = floatval($invoiceXml->InvoiceTotals->InvoiceTotal);
    $expectedDiscounts = (100 / 1.25) * 0.5 + 5;
    $expectedCharges = (100 / 1.25) * 0.5;
    $expectedTotal = 100 - $expectedDiscounts + $expectedCharges;
    $this->assertEqualsWithDelta($totalDiscounts, $expectedDiscounts, 0.00001);
    $this->assertEqualsWithDelta($totalCharges, $expectedCharges, 0.00001);
    $this->assertEqualsWithDelta($invoiceTotal, $expectedTotal, 0.00001);
  }

}
