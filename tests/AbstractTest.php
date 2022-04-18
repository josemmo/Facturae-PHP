<?php
namespace josemmo\Facturae\Tests;

use PHPUnit\Framework\TestCase;
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeParty;

abstract class AbstractTest extends TestCase {

  const OUTPUT_DIR = __DIR__ . "/output";
  const CERTS_DIR = __DIR__ . "/certs";
  const FACTURAE_CERT_PASS = "1234";
  const WEBSERVICES_CERT_PASS = "IZProd2021";
  const NOTIFICATIONS_EMAIL = "josemmo@pm.me";
  const COOKIES_PATH = self::OUTPUT_DIR . "/cookies.txt";

  /**
   * Get base invoice
   * @param  string|null $schema FacturaE schema
   * @return Facturae            Invoice instance
   */
  protected function getBaseInvoice($schema=null) {
    $fac = is_null($schema) ? new Facturae() : new Facturae($schema);
    $fac->setNumber('FAC' . date('Ym'), '0001');
    $fac->setIssueDate(date('Y-m-d'));
    $fac->setSeller(new FacturaeParty([
      "taxNumber" => "A00000000",
      "name"      => "Perico de los Palotes S.A.",
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
   * Validate Invoice XML
   * @param string  $path              Invoice path
   * @param boolean $validateSignature Validate signature
   */
  protected function validateInvoiceXML($path, $validateSignature=false) {
    // Prepare file to upload
    if (function_exists('curl_file_create')) {
      $postFile = curl_file_create($path);
    } else {
      $postFile = "@" . realpath($path);
    }

    // Send upload request
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_URL => "http://plataforma.firma-e.com/VisualizadorFacturae/index2.jsp",
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => array(
        "referencia" => $postFile,
        "valContable" => "on",
        "valFirma" => $validateSignature ? "on" : "off",
        "aceptarCondiciones" => "on",
        "submit" => "Siguiente"
      ),
      CURLOPT_COOKIEJAR => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    unset($ch);
    if (strpos($res, "window.open('facturae.jsp'") === false) {
      $this->expectException(\UnexpectedValueException::class);
    }

    // Fetch results
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_URL => "http://plataforma.firma-e.com/VisualizadorFacturae/facturae.jsp",
      CURLOPT_COOKIEFILE => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    unset($ch);

    // Validate results
    $this->assertNotEmpty($res);
    $this->assertStringContainsString('euro_ok.png', $res, 'Invalid XML Format');
    if ($validateSignature) {
      $this->assertStringContainsString('>Nivel de Firma Válido<', $res, 'Invalid Signature');
    }
    if (strpos($res, '>Sellos de Tiempo<') !== false) {
      $this->assertStringContainsString('>XAdES_T<', $res, 'Invalid Timestamp');
    }
  }

}
