<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Common\FacturaeSigner;
use RuntimeException;

final class SignerTest extends AbstractTest {

  /**
   * Get signer instance
   * @return FacturaeSigner Signer instance
   */
  private function getSigner() {
    $signer = new FacturaeSigner();
    $signer->loadPkcs12(self::CERTS_DIR . '/facturae.p12', self::FACTURAE_CERT_PASS);
    $signer->setTimestampServer('http://tss.accv.es:8318/tsa');
    return $signer;
  }


  public function testCanLoadPemStrings() {
    $signer = new FacturaeSigner();
    $signer->addCertificate(file_get_contents(self::CERTS_DIR . '/facturae-public.pem'));
    $signer->setPrivateKey(file_get_contents(self::CERTS_DIR . '/facturae-private.pem'), self::FACTURAE_CERT_PASS);
    $this->assertTrue($signer->canSign());
  }


  public function testCanLoadStoreBytes() {
    $signer = new FacturaeSigner();
    $signer->loadPkcs12(file_get_contents(self::CERTS_DIR . '/facturae.p12'), self::FACTURAE_CERT_PASS);
    $this->assertTrue($signer->canSign());
  }


  public function testCanRegenerateIds() {
    $signer = new FacturaeSigner();

    $oldSignatureId = $signer->signatureId;
    $signer->regenerateIds();
    $this->assertNotEquals($oldSignatureId, $signer->signatureId);

    $oldSignatureId = $signer->signatureId;
    $signer->regenerateIds();
    $this->assertNotEquals($oldSignatureId, $signer->signatureId);
  }


  public function testCannotSignWithoutKey() {
    $this->expectException(RuntimeException::class);
    $signer = new FacturaeSigner();
    $xml = $this->getBaseInvoice()->export();
    $signer->sign($xml);
  }


  public function testCannotSignInvalidDocuments() {
    $this->expectException(RuntimeException::class);
    $this->getSigner()->sign('<hello><world /></hello>');
  }


  public function testCanSignValidDocuments() {
    $xml = $this->getBaseInvoice()->export();
    $signedXml = $this->getSigner()->sign($xml);
    $this->assertStringContainsString('</ds:SignatureValue>', $signedXml);
  }


  public function testNormalizesLineBreaks() {
    $xml = '<fe:Facturae xmlns:fe="http://www.facturae.es/Facturae/2014/v3.2.1/Facturae">' .
      "    <test>This contains\r\nWindows line breaks</test>\n" .
      "    <test>This contains\rclassic MacOS line breaks</test>\n" .
      '</fe:Facturae>';
    $signedXml = $this->getSigner()->sign($xml);
    $this->assertStringNotContainsString("\r", $signedXml);
  }


  public function testCannotTimestampWithoutTsaDetails() {
    $this->expectException(RuntimeException::class);
    $signer = new FacturaeSigner();
    $signer->timestamp(
      '<fe:Facturae>
        <ds:SignatureValue></ds:SignatureValue>
        <xades:QualifyingProperties></xades:QualifyingProperties>
      </fe:Facturae>'
    );
  }


  public function testCanTimestampSignedDocuments() {
    $signer = $this->getSigner();
    $xml = $this->getBaseInvoice()->export();
    $signedXml = $signer->sign($xml);
    $timestampedXml = $signer->timestamp($signedXml);
    $this->assertStringContainsString('</xades:SignatureTimeStamp>', $timestampedXml);
  }

}
