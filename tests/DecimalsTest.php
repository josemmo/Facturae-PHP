<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use PHPUnit\Framework\TestCase;

final class DecimalsTest extends TestCase {

  const NUM_OF_TESTS = 1000;
  const ITEMS_PER_INVOICE = 3;

  const PRICE_DECIMALS = 8;
  const QUANTITY_DECIMALS = 6;
  const TAX_DECIMALS = 6;


  /**
   * Run test on a random invoice
   *
   * @param  string  $schema FacturaE schema
   * @return boolean         Success
   */
  private function _runTest($schema) {
    // Creamos una factura estándar
    $fac = new Facturae($schema);
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

    // Añadimos elementos con importes aleatorios
    $unitPriceTotal = 0;
    $pricePow = 10 ** self::PRICE_DECIMALS;
    $quantityPow = 10 ** self::QUANTITY_DECIMALS;
    $taxPow = 10 ** self::TAX_DECIMALS;
    for ($i=0; $i<self::ITEMS_PER_INVOICE; $i++) {
      $unitPrice = mt_rand(1, $pricePow) / $pricePow;
      $quantity = mt_rand(1, $quantityPow*10) / $quantityPow;
      $specialTax = mt_rand(1, $taxPow*20) / $taxPow;
      $unitPriceTotal += $unitPrice * $quantity;
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

    // Validamos los totales de la factura
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
