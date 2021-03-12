<?php
namespace josemmo\Facturae\Common;

/**
 * Gets both public and private keys from a PKCS#12 certificate store or a PEM
 * file (X.509 certificate).
 */
class KeyPairReader {

  private $publicKey;
  private $privateKey;


  /**
   * Get public key
   * @return string|null Public Key
   */
  public function getPublicKey() {
    return $this->publicKey;
  }


  /**
   * Get private key
   * @return string|null Private Key
   */
  public function getPrivateKey() {
    return $this->privateKey;
  }


  /**
   * KeyPairReader constructor
   *
   * @param string      $publicPath  Path to public key in PEM or PKCS#12 file
   * @param string|null $privatePath Path to private key (null for PKCS#12)
   * @param string      $passphrase  Private key passphrase
   */
  public function __construct($publicPath, $privatePath=null, $passphrase="") {
    if (is_null($privatePath)) {
      $this->readPkcs12($publicPath, $passphrase);
    } else {
      $this->readX509($publicPath, $privatePath, $passphrase);
    }
  }


  /**
   * Read a X.509 certificate and PEM encoded private key
   *
   * @param string $publicPath  Path to public key PEM file
   * @param string $privatePath Path to private key PEM file
   * @param string $passphrase  Private key passphrase
   */
  private function readX509($publicPath, $privatePath, $passphrase) {
    if (!is_file($publicPath) || !is_file($privatePath)) return;
    $this->publicKey = openssl_x509_read(file_get_contents($publicPath));
    $this->privateKey = openssl_pkey_get_private(
      file_get_contents($privatePath),
      $passphrase
    );
  }


  /**
   * Read a PKCS#12 Certificate Store
   *
   * @param string $certPath   The certificate store file name
   * @param string $passphrase Password for unlocking the PKCS#12 file
   */
  private function readPkcs12($certPath, $passphrase) {
    if (!is_file($certPath)) return false;
    if (openssl_pkcs12_read(file_get_contents($certPath), $certs, $passphrase)) {
      $this->publicKey = openssl_x509_read($certs['cert']);
      $this->privateKey = openssl_pkey_get_private($certs['pkey']);
    }
  }

}
