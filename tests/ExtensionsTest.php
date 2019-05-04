<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeCentre;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\Tests\Extensions\DisclaimerExtension;

final class ExtensionsTest extends AbstractTest {

  const FILE_PATH = self::OUTPUT_DIR . "/salida-extensiones.xsig";
  const FB2B_XSD_PATH = "https://administracionelectronica.gob.es/ctt/resources/Soluciones/2811/Descargas/Extension%20FACEB2B%20v1-1.xsd";

  /**
   * Test extensions
   */
  public function testExtensions() {
    // Creamos una factura estándar
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
    $fac->addItem("Línea de producto", 100, 1, Facturae::TAX_IVA, 10);

    // Obtener la extensión de FACeB2B y establecemos la entidad pública
    $b2b = $fac->getExtension('Fb2b');
    $b2b->setPublicOrganismCode('E00003301');
    $b2b->setContractReference('333000');

    // Añadimos los remitentes (vendedores) de FACeB2B
    $b2b->addCentre(new FacturaeCentre([
      "code" => "ES12345678Z0002",
      "name" => "Unidad DIRe Vendedora 0002",
      "role" => FacturaeCentre::ROLE_B2B_SELLER
    ]), false);
    $b2b->addCentre(new FacturaeCentre([
      "code" => "ES12345678Z0003",
      "name" => "Unidad DIRe Fiscal 0003",
      "role" => FacturaeCentre::ROLE_B2B_FISCAL
    ]), false);

    // Añadimos los destinatarios (compradores) de FACeB2B
    $b2b->setReceiver(new FacturaeCentre([
      "code" => "51558103JES0001",
      "name" => "Centro administrativo receptor"
    ]));
    $b2b->addCentre(new FacturaeCentre([
      "code" => "ESB123456740002",
      "name" => "Unidad DIRe Compradora 0002",
      "role" => FacturaeCentre::ROLE_B2B_BUYER
    ]));
    $b2b->addCentre(new FacturaeCentre([
      "code" => "ESB123456740003",
      "name" => "Unidad DIRe Fiscal 0003",
      "role" => FacturaeCentre::ROLE_B2B_FISCAL
    ]));
    $b2b->addCentre(new FacturaeCentre([
      "code" => "ESB123456740004",
      "role" => FacturaeCentre::ROLE_B2B_COLLECTOR
    ]));

    // Añadimos la extensión externa de prueba
    $disclaimer = $fac->getExtension(DisclaimerExtension::class);
    $disclaimer->enable();

    // Exportamos la factura
    $fac->sign(self::CERTS_DIR . "/facturae.pfx", null, self::FACTURAE_CERT_PASS);
    $success = ($fac->export(self::FILE_PATH) !== false);
    $this->assertTrue($success);

    $rawXml = file_get_contents(self::FILE_PATH);
    $extXml = explode('<Extensions>', $rawXml);
    $extXml = explode('</Extensions>', $extXml[1])[0];

    // Validamos la parte de FACeB2B
    $faceXml = new \DOMDocument();
    $faceXml->loadXML($extXml);
    $isValidXml = $faceXml->schemaValidate(self::FB2B_XSD_PATH);
    $this->assertTrue($isValidXml);

    // Validamos la ejecución de DisclaimerExtension
    $disclaimerPos = strpos($rawXml, '<LegalReference>' . $disclaimer->getDisclaimer() . '</LegalReference>');
    $this->assertTrue($disclaimerPos !== false);
  }

}
