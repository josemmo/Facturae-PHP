<?php
namespace josemmo\Facturae\FacturaeTraits;

use josemmo\Facturae\Common\FacturaeSigner;

/**
 * Implements all properties and methods needed for an instantiable
 * Facturae to be signed and time stamped.
 */
trait SignableTrait {
  /** @var FacturaeSigner|null */
  protected $signer = null;

  /**
   * Get signer instance
   * @return FacturaeSigner Signer instance
   */
  public function getSigner() {
    if ($this->signer === null) {
      $this->signer = new FacturaeSigner();
    }
    return $this->signer;
  }


  /**
   * Set signing time
   * @param  int|string $time Time of the signature
   * @return self             This instance
   */
  public function setSigningTime($time) {
    $this->getSigner()->setSigningTime($time);
    return $this;
  }


  /**
   * Set signing time
   *
   * Same as `Facturae::setSigningTime()` for backwards compatibility
   * @param  int|string $time Time of the signature
   * @return self             This instance
   * @deprecated 1.7.4 Renamed to `Facturae::setSigningTime()`.
   */
  public function setSignTime($time) {
    return $this->setSigningTime($time);
  }


  /**
   * Set timestamp server
   * @param string $server Timestamp Authority URL
   * @param string $user   TSA User
   * @param string $pass   TSA Password
   */
  public function setTimestampServer($server, $user=null, $pass=null) {
    $this->getSigner()->setTimestampServer($server, $user, $pass);
  }


  /**
   * Sign
   * @param  string      $publicPath  Path to public key PEM file or PKCS#12 certificate store
   * @param  string|null $privatePath Path to private key PEM file (should be null in case of PKCS#12)
   * @param  string      $passphrase  Private key passphrase
   * @return boolean                  Success
   */
  public function sign($publicPath, $privatePath=null, $passphrase="") {
    $signer = $this->getSigner();
    $signer->setSigningKey($publicPath, $privatePath, $passphrase);
    return $signer->canSign();
  }


  /**
   * Inject signature and timestamp (if needed)
   * @param  string $xml Unsigned XML document
   * @return string      Signed XML document
   */
  protected function injectSignatureAndTimestamp($xml) {
    // Make sure we have all we need to sign the document
    if ($this->signer === null || $this->signer->canSign() === false) {
      return $xml;
    }

    // Sign and timestamp document
    $xml = $this->signer->sign($xml);
    if ($this->signer->canTimestamp()) {
      $xml = $this->signer->timestamp($xml);
    }
    return $xml;
  }

}
