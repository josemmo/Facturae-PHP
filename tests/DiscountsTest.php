<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use PHPUnit\Framework\TestCase;

final class DiscountsTest extends TestCase {

  /**
   * Get base invoice
   * @return Facturae Base invoice
   */
  private function _getBaseInvoice() {
    $fac = new Facturae();
    $fac->setNumber('EMP201712', '0003');
    $fac->setIssueDate('2017-12-01');
    $fac->setSeller(new FacturaeParty([
      "taxNumber" => "A00000000",
      "name"      => "Perico el de los Palotes S.A.",
      "address"   => "C/ Falsa, 123",
      "postCode"  => "12345",
      "town"      => "Madrid",
      "province"  => "Madrid"
    ]));
    $fac->setBuyer(new FacturaeParty([
      "isLegalEntity" => false,
      "taxNumber"     => "00000000A",
      "name"          => "Antonio",
      "firstSurname"  => "García",
      "lastSurname"   => "Pérez",
      "address"       => "Avda. Mayor, 7",
      "postCode"      => "54321",
      "town"          => "Madrid",
      "province"      => "Madrid"
    ]));
    return $fac;
  }


  /**
   * Test invoice item discounts
   */
  public function testItemDiscounts() {
    $fac = $this->_getBaseInvoice();
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
      $expectedGross = array_shift($expectedGrossAmounts);
      $this->assertEquals($itemGross, $expectedGross, '', 0.00001);
    }
  }


  /**
   * Test general discounts
   */
  public function testGeneralDiscounts() {
    $fac = $this->_getBaseInvoice();
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
    $this->assertEquals($totalDiscounts, $expectedDiscounts, '', 0.00001);
    $this->assertEquals($totalCharges, $expectedCharges, '', 0.00001);
    $this->assertEquals($invoiceTotal, $expectedTotal, '', 0.00001);
  }

}
