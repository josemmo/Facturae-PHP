<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaeCentre;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\Face\FaceClient;
use josemmo\Facturae\Face\Faceb2bClient;

final class WebservicesTest extends AbstractTest {

  /**
   * Check environment
   */
  private function checkEnv() {
    $isCI = getenv('CI');
    $testWS = getenv('TEST_WEBSERVICES');
    if ($isCI && !$testWS) $this->markTestSkipped('Environment conditions not met');
  }


  /**
   * Get Webservices base invoice
   * @return Facturae Invoice instance
   */
  private function getWsBaseInvoice() {
    $fac = new Facturae();
    $fac->setNumber('PRUEBA-' . date('ym'), date('Hms'));
    $fac->setIssueDate(date('Y-m-d'));
    $fac->setSeller(new FacturaeParty([
      "isLegalEntity" => false,
      "taxNumber"     => "99999999R",
      "name"          => "Juan",
      "firstSurname"  => "Español",
      "lastSurname"   => "Español",
      "address"       => "C/ Falsa, 123",
      "postCode"      => "12345",
      "town"          => "Madrid",
      "province"      => "Madrid"
    ]));
    $fac->addItem('Producto de prueba', 1, 1, Facturae::TAX_IVA, 21);
    $fac->addLegalLiteral('Esta factura es una PRUEBA y no debe considerarse legalmente válida');
    return $fac;
  }


  /**
   * Test FACe
   */
  public function testFace() {
    $this->checkEnv();

    $face = new FaceClient(self::CERTS_DIR . "/webservices.p12", null, self::WEBSERVICES_CERT_PASS);
    $face->setProduction(false);

    // Test misc. methods
    $this->assertFalse(empty($face->getStatus()->estados));
    $this->assertFalse(empty($face->getAdministrations()->administraciones));
    $this->assertFalse(empty($face->getUnits('E04921501')->relaciones));
    $this->assertFalse(empty($face->getNifs('E04921501')->nifs));

    // Generate invoice
    $fac = $this->getWsBaseInvoice();
    $fac->setBuyer(new FacturaeParty([
      "taxNumber" => "V28000024",
      "name"      => "Banco de España",
      "address"   => "Calle de Alcalá, 48",
      "postCode"  => "28014",
      "town"      => "Madrid",
      "province"  => "Madrid",
      "centres"   => [
        new FacturaeCentre([
          "role" => FacturaeCentre::ROLE_GESTOR,
          "code" => "GE0010539",
          "name" => "Adquisiciones y Servicios Generales"
        ]),
        new FacturaeCentre([
          "role" => FacturaeCentre::ROLE_TRAMITADOR,
          "code" => "GE0010537",
          "name" => "Asg. Mantenimiento y Servicios Comunes"
        ]),
        new FacturaeCentre([
          "role" => FacturaeCentre::ROLE_CONTABLE,
          "code" => "GE0010538",
          "name" => "Banco de España"
        ])
      ]
    ]));
    $fac->sign(self::CERTS_DIR . "/webservices.p12", null, self::WEBSERVICES_CERT_PASS);

    // Send invoice
    $invoiceFile = new FacturaeFile();
    $invoiceFile->loadData($fac->export(), "factura-de-prueba.xsig");
    $res = $face->sendInvoice(self::NOTIFICATIONS_EMAIL, $invoiceFile);
    $this->assertEquals(intval($res->resultado->codigo), 0);
    $this->assertFalse(empty($res->factura->numeroRegistro));

    // Cancel invoice
    $res = $face->cancelInvoice($res->factura->numeroRegistro,
      "Factura de prueba autogenerada por " . Facturae::USER_AGENT);
    $this->assertEquals(intval($res->resultado->codigo), 0);
    $this->assertFalse(empty($res->factura->mensaje));

    // Get invoice status
    $res = $face->getInvoices($res->factura->numeroRegistro);
    $this->assertEquals(intval($res->resultado->codigo), 0);
  }


  /**
   * Test FACeB2B
   */
  public function testFaceb2b() {
    $this->checkEnv();

    $faceb2b = new Faceb2bClient(self::CERTS_DIR . "/webservices.p12", null, self::WEBSERVICES_CERT_PASS);
    $faceb2b->setProduction(false);

    // Test misc. methods
    $this->assertFalse(empty($faceb2b->getCodes()->codes));
    $this->assertEquals(intval($faceb2b->getRegisteredInvoices()->resultStatus->code), 0);
    $this->assertEquals(intval($faceb2b->getInvoiceCancellations()->resultStatus->code), 0);

    // Generate invoice
    $fac = $this->getWsBaseInvoice();
    $fac->setBuyer(new FacturaeParty([
      "taxNumber" => "A78923125",
      "name"      => "Teléfonica Móviles España, S.A.U.",
      "address"   => "Calle Gran Vía, 28",
      "postCode"  => "28013",
      "town"      => "Madrid",
      "province"  => "Madrid"
    ]));
    $fac->getExtension('Fb2b')->setReceiver(new FacturaeCentre([
      "code" => "ESA789231250000",
      "name" => "Centro administrativo receptor"
    ]));
    $fac->sign(self::CERTS_DIR . "/webservices.p12", null, self::WEBSERVICES_CERT_PASS);

    // Send invoice
    $invoiceFile = new FacturaeFile();
    $invoiceFile->loadData($fac->export(), "factura-de-prueba.xsig");
    $res = $faceb2b->sendInvoice($invoiceFile);
    $this->assertEquals(intval($res->resultStatus->code), 0);
    $this->assertFalse(empty($res->invoiceDetail->registryNumber));
    $registryNumber = $res->invoiceDetail->registryNumber;

    // Cancel invoice
    $res = $faceb2b->requestInvoiceCancellation($registryNumber,
      "C002", "Factura de prueba autogenerada por " . Facturae::USER_AGENT);
    $this->assertEquals(intval($res->resultStatus->code), 0);

    // Confirm cancellation
    $res = $faceb2b->getInvoiceDetails($registryNumber);
    $this->assertEquals(intval($res->resultStatus->code), 0);
    $this->assertEquals(strval($res->invoiceDetail->cancellationInfo->reason), "C002");
  }

}
