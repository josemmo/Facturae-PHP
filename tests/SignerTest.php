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
    $signer->setSigningKey(self::CERTS_DIR . "/webservices.p12", null, self::WEBSERVICES_CERT_PASS);
    $signer->setTimestampServer('http://tss.accv.es:8318/tsa');
    return $signer;
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
