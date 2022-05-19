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
    $fac->addItem(new FacturaeItem([
      "name" => "Test item #1",
      "unitPriceWithoutTax" => 90,
      "taxes" => [
        Facturae::TAX_IVA => ["rate"=>21, "surcharge"=>5.2]
      ]
    ]));
    $fac->addItem(new FacturaeItem([
      "name" => "Test item #2",
      "unitPriceWithoutTax" => 50,
      "taxes" => [
        Facturae::TAX_IVA => ["rate"=>10, "surcharge"=>1.4]
      ]
    ]));
    $fac->addCharge('10% charge', 10);
    $fac->addDiscount('10% discount', 10);
    $fac->addDiscount('Fixed amount discount', 25.20, false);

    // Generate invoice and validate output
    $invoiceXml = new \SimpleXMLElement($fac->export());
    $invoiceXml = $invoiceXml->Invoices->Invoice[0];
    $totalDiscounts = floatval($invoiceXml->InvoiceTotals->TotalGeneralDiscounts);
    $totalCharges = floatval($invoiceXml->InvoiceTotals->TotalGeneralSurcharges);
    $invoiceTotal = floatval($invoiceXml->InvoiceTotals->InvoiceTotal);
    $this->assertEqualsWithDelta($totalDiscounts,  39.20, 0.00001);
    $this->assertEqualsWithDelta($totalCharges,    14.00, 0.00001);
    $this->assertEqualsWithDelta($invoiceTotal,   134.60, 0.00001);
  }

}
