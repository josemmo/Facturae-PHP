<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;

final class OverseasTest extends AbstractTest {

  const FILE_PATH = self::OUTPUT_DIR . "/salida-overseas.xml";

  /**
   * Test overseas address
   */
  public function testOverseasAddress() {
    $fac = $this->getBaseInvoice();
    $fac->getBuyer()->town = "Coimbra";
    $fac->getBuyer()->province = "Beira";
    $fac->getBuyer()->address = "Rua do Brasil 284";
    $fac->getBuyer()->postCode = "3030-775";
    $fac->getBuyer()->countryCode = "PRT";
    $fac->addItem("Línea de producto", 100, 1, Facturae::TAX_IVA, 21);

    // Validate invoice as-is
    $fac->export(self::FILE_PATH);
    $this->validateInvoiceXML(self::FILE_PATH);
    $this->assertEquals("R", $fac->getSeller()->getResidenceTypeCode());
    $this->assertEquals("U", $fac->getBuyer()->getResidenceTypeCode());

    // Switch buyer to United States
    $fac->getBuyer()->countryCode = "USA";
    $this->assertEquals("E", $fac->getBuyer()->getResidenceTypeCode());

    // Force European-resident type code
    $fac->getBuyer()->isEuropeanUnionResident = true;
    $this->assertEquals("U", $fac->getBuyer()->getResidenceTypeCode());

    // Force non-European-resident type code
    $fac->getBuyer()->countryCode = "PRT";
    $fac->getBuyer()->isEuropeanUnionResident = false;
    $this->assertEquals("E", $fac->getBuyer()->getResidenceTypeCode());
  }

}
