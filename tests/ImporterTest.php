<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Common\FacturaeImporter;
use josemmo\Facturae\CorrectiveDetails;
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeCentre;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaePayment;

/**
 * Roundtrip tests for FacturaeImporter.
 *
 * Each test builds a Facturae invoice, exports it to XML, re-imports it with
 * FacturaeImporter::loadXml(), exports again and then asserts structural and
 * numeric equivalence between both outputs.
 */
final class ImporterTest extends AbstractTest {

  // -------------------------------------------------------------------------
  // Data providers
  // -------------------------------------------------------------------------

  /**
   * @return array<string,array<string>>
   */
  public function schemaProvider(): array {
    return [
      'Schema 3.2'   => [Facturae::SCHEMA_3_2],
      'Schema 3.2.1' => [Facturae::SCHEMA_3_2_1],
      'Schema 3.2.2' => [Facturae::SCHEMA_3_2_2],
    ];
  }


  // -------------------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------------------

  /**
   * Build a comprehensive invoice with most addressable fields.
   *
   * @param  string   $schema FacturaE schema version
   * @return Facturae         Invoice instance
   */
  private function buildRichInvoice(string $schema): Facturae {
    $fac = new Facturae($schema);

    $fac->setNumber('TEST2026', '0042');
    $fac->setIssueDate('2026-03-23');
    $fac->setBillingPeriod('2026-02-01', '2026-02-28');
    $fac->setDescription('Factura de prueba de roundtrip');
    $fac->setReferences('REF-FILE-001', 'TXN-001', 'CTR-001');
    $fac->addLegalLiteral('Texto legal de referencia');
    $fac->setAdditionalInformation('Información adicional de prueba');

    $fac->setSeller(new FacturaeParty([
      'taxNumber' => 'A00000000',
      'name'      => 'Empresa Vendedora S.A.',
      'tradeName' => 'EV',
      'address'   => 'C/ Vendedor, 1',
      'postCode'  => '28001',
      'town'      => 'Madrid',
      'province'  => 'Madrid',
      'email'     => 'ventas@ev.test',
      'phone'     => '910000001',
    ]));

    $fac->setBuyer(new FacturaeParty([
      'isLegalEntity' => false,
      'taxNumber'     => '00000000A',
      'name'          => 'Juan',
      'firstSurname'  => 'García',
      'lastSurname'   => 'López',
      'address'       => 'Avda. Comprador, 5',
      'postCode'      => '08001',
      'town'          => 'Barcelona',
      'province'      => 'Barcelona',
      'email'         => 'juan.garcia@comprador.test',
      'centres'       => [
        new FacturaeCentre([
          'role'     => FacturaeCentre::ROLE_GESTOR,
          'code'     => 'L01280796',
          'name'     => 'Gestoría Central',
          'address'  => 'Calle Gestión, 2',
          'postCode' => '28002',
          'town'     => 'Madrid',
          'province' => 'Madrid',
        ]),
      ],
    ]));

    $fac->setCorrective(new CorrectiveDetails([
      'invoiceNumber'               => '0041',
      'invoiceSeriesCode'           => 'TEST2026',
      'reason'                      => '10',
      'taxPeriodStart'              => '2026-01-01',
      'taxPeriodEnd'                => '2026-01-31',
      'correctionMethod'            => CorrectiveDetails::METHOD_DIFFERENCES,
      'additionalReasonDescription' => 'Corrección de detalle de operación',
    ]));

    // Item with unit price without tax and discounts/charges
    $fac->addItem(new FacturaeItem([
      'name'               => 'Producto A',
      'description'        => 'Descripción detallada del producto A',
      'unitPriceWithoutTax' => 100.00,
      'quantity'           => 2,
      'taxes'              => [
        Facturae::TAX_IVA  => ['rate' => 21, 'surcharge' => 0],
      ],
      'discounts' => [['reason' => 'Descuento fidelidad', 'rate' => 10]],
    ]));

    // Item with withheld tax
    $fac->addItem(new FacturaeItem([
      'name'               => 'Servicio B',
      'unitPriceWithoutTax' => 500.00,
      'taxes'              => [
        Facturae::TAX_IVA  => ['rate' => 21, 'isWithheld' => false],
        Facturae::TAX_IRPF => ['rate' => 15, 'isWithheld' => true],
      ],
    ]));

    // General discount and charge on invoice subtotal
    $fac->addDiscount('Descuento global', 5);
    $fac->addCharge('Cargo adicional', 50, false);

    $fac->addPayment(new FacturaePayment([
      'method'  => FacturaePayment::TYPE_TRANSFER,
      'dueDate' => '2026-04-23',
      'amount'  => 1000.00,
      'iban'    => 'ES6000491500051234567892',
      'bic'     => 'AAABESMMXXX',
    ]));

    return $fac;
  }


  /**
   * Parse an XML string into a SimpleXMLElement for xpath access.
   *
   * @param  string           $xml XML string
   * @return \SimpleXMLElement     Parsed element
   */
  private function parseXml(string $xml): \SimpleXMLElement {
    $element = new \SimpleXMLElement($xml);
    $element->registerXPathNamespace('f', (string) $element->getNamespaces(true)['fe'] ?? '');
    return $element;
  }


  // -------------------------------------------------------------------------
  // Tests
  // -------------------------------------------------------------------------

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesSchemaVersion(string $schema): void {
    $xmlA = (new Facturae($schema))
      ->setNumber('S', '1')
      ->setIssueDate('2026-01-01')
      ->setSeller(new FacturaeParty(['taxNumber' => 'A00000000', 'name' => 'Seller', 'address' => 'St', 'postCode' => '00000', 'town' => 'X', 'province' => 'X']))
      ->setBuyer(new FacturaeParty(['taxNumber' => '00000000T', 'name' => 'Buyer', 'address' => 'St', 'postCode' => '00000', 'town' => 'X', 'province' => 'X']))
      ->addItem(new FacturaeItem(['name' => 'Item', 'unitPriceWithoutTax' => 10, 'taxes' => [Facturae::TAX_IVA => 21]]))
      ->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame($schema, $imported->getSchemaVersion());
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesInvoiceNumber(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $number = $imported->getNumber();

    $this->assertSame('TEST2026', $number['serie']);
    $this->assertSame('0042', $number['number']);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesIssueDate(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame('2026-03-23', date('Y-m-d', $imported->getIssueDate()));
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesPartyTaxNumbers(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame('A00000000', $imported->getSeller()->taxNumber);
    $this->assertSame('00000000A', $imported->getBuyer()->taxNumber);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesSellerName(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame('Empresa Vendedora S.A.', $imported->getSeller()->name);
    $this->assertFalse($imported->getBuyer()->isLegalEntity);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesAdministrativeCentres(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $buyer = $imported->getBuyer();
    $this->assertCount(1, $buyer->centres);
    $this->assertSame('L01280796', $buyer->centres[0]->code);
    $this->assertSame(FacturaeCentre::ROLE_GESTOR, $buyer->centres[0]->role);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesItemCount(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertCount(2, $imported->getItems());
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesInvoiceTotals(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();
    $elA = new \SimpleXMLElement($xmlA);

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $xmlB = $imported->export();
    $elB = new \SimpleXMLElement($xmlB);

    $invoiceA = $elA->Invoices->Invoice[0];
    $invoiceB = $elB->Invoices->Invoice[0];

    $this->assertEqualsWithDelta(
      (float) $invoiceA->InvoiceTotals->InvoiceTotal,
      (float) $invoiceB->InvoiceTotals->InvoiceTotal,
      0.01
    );
    $this->assertEqualsWithDelta(
      (float) $invoiceA->InvoiceTotals->TotalGrossAmount,
      (float) $invoiceB->InvoiceTotals->TotalGrossAmount,
      0.01
    );
    $this->assertEqualsWithDelta(
      (float) $invoiceA->InvoiceTotals->TotalTaxOutputs,
      (float) $invoiceB->InvoiceTotals->TotalTaxOutputs,
      0.01
    );
    $this->assertEqualsWithDelta(
      (float) $invoiceA->InvoiceTotals->TotalTaxesWithheld,
      (float) $invoiceB->InvoiceTotals->TotalTaxesWithheld,
      0.01
    );
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesPaymentMethod(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $payments = $imported->getPayments();

    $this->assertCount(1, $payments);
    $this->assertSame(FacturaePayment::TYPE_TRANSFER, $payments[0]->method);
    $this->assertSame('ES6000491500051234567892', $payments[0]->iban);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesLegalLiterals(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame(['Texto legal de referencia'], $imported->getLegalLiterals());
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesBillingPeriod(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $period = $imported->getBillingPeriod();

    $this->assertSame('2026-02-01', date('Y-m-d', $period['startDate']));
    $this->assertSame('2026-02-28', date('Y-m-d', $period['endDate']));
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesDescription(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $this->assertSame('Factura de prueba de roundtrip', $imported->getDescription());
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesCorrectiveDetails(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $corrective = $imported->getCorrective();

    $this->assertNotNull($corrective);
    $this->assertSame('0041', $corrective->invoiceNumber);
    $this->assertSame('10', $corrective->reason);
    $this->assertSame(CorrectiveDetails::METHOD_DIFFERENCES, $corrective->correctionMethod);
  }

  /**
   * @dataProvider schemaProvider
   */
  public function testRoundtripPreservesItemLineAmounts(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xmlA = $fac->export();

    $imported = (new FacturaeImporter())->loadXml($xmlA);
    $xmlB = $imported->export();

    $itemsA = (new \SimpleXMLElement($xmlA))->Invoices->Invoice[0]->Items;
    $itemsB = (new \SimpleXMLElement($xmlB))->Invoices->Invoice[0]->Items;

    $linesA = iterator_to_array($itemsA->InvoiceLine);
    $linesB = iterator_to_array($itemsB->InvoiceLine);

    $this->assertCount(count($linesA), $linesB);
    foreach ($linesA as $index => $lineA) {
      $lineB = $linesB[$index];
      $this->assertEqualsWithDelta(
        (float) $lineA->GrossAmount,
        (float) $lineB->GrossAmount,
        0.01,
        "GrossAmount mismatch on line {$index}"
      );
      $this->assertEqualsWithDelta(
        (float) $lineA->TaxesOutputs->Tax[0]->TaxableBase->TotalAmount,
        (float) $lineB->TaxesOutputs->Tax[0]->TaxableBase->TotalAmount,
        0.01,
        "TaxableBase mismatch on line {$index}"
      );
    }
  }


  /**
   * Verify that Facturae implements JsonSerializable and exposes all
   * protected properties via json_encode().
   */
  public function testJsonSerializableExposesProperties(): void {
    $fac = $this->getBaseInvoice();
    $fac->addItem(new FacturaeItem([
      'name'               => 'Artículo JSON',
      'unitPriceWithoutTax' => 50,
      'taxes'              => [Facturae::TAX_IVA => 21],
    ]));

    $this->assertInstanceOf(\JsonSerializable::class, $fac);

    $json = json_encode($fac);
    $this->assertNotFalse($json);

    $data = json_decode($json, true);
    $this->assertIsArray($data);
    $this->assertArrayHasKey('header', $data);
    $this->assertArrayHasKey('parties', $data);
    $this->assertArrayHasKey('items', $data);
    $this->assertArrayHasKey('payments', $data);
    $this->assertArrayHasKey('version', $data);
  }


  /**
   * Verify that Facturae::injectXml() is a thin alias for FacturaeImporter.
   *
   * @dataProvider schemaProvider
   */
  public function testInjectXmlStaticHelper(string $schema): void {
    $fac = $this->buildRichInvoice($schema);
    $xml = $fac->export();

    $imported = Facturae::injectXml($xml);
    $this->assertInstanceOf(Facturae::class, $imported);
    $this->assertSame($schema, $imported->getSchemaVersion());

    $number = $imported->getNumber();
    $this->assertSame('TEST2026', $number['serie']);
    $this->assertSame('0042', $number['number']);
  }


  /**
   * Verify that loading invalid XML throws an exception.
   */
  public function testLoadInvalidXmlThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    (new FacturaeImporter())->loadXml('not-xml-at-all');
  }
}
