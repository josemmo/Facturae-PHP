<?php
namespace josemmo\Facturae\Common;

/**
 * Methods for getting both public and private keys from PKCS#12 certificate
 * stores or PEM files (X.509 certificate).
 */
trait KeyPairReaderTrait {
  /**
   * Array of PEM strings
   * @var string[]
   */
  protected $publicChain = [];


  /**
   * Decrypted private key
   * @var \OpenSSLAsymmetricKey|resource|null
   */
  protected $privateKey = null;


  /**
   * Add certificate to public chain
   *
   * @param \OpenSSLCertificate|resource|string $certificate OpenSSL instance, PEM string or filepath
   * @return boolean Success result
   */
  public function addCertificate($certificate) {
    // Read file from path
    if (is_string($certificate) && strpos($certificate, '-----BEGIN CERTIFICATE-----') === false) {
      $certificate = file_get_contents($certificate);
    }

    // Validate and normalize certificate
    if (empty($certificate)) {
      return false;
    }
    if (!openssl_x509_export($certificate, $normalizedCertificate)) {
      return false;
    }

    // Append certificate to chain
    $this->publicChain[] = $normalizedCertificate;
    return true;
  }


  /**
   * Set private key
   *
   * @param  \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string $privateKey OpenSSL instance, PEM string or filepath
   * @param  string                                                    $passphrase Passphrase to decrypt (optional)
   * @return boolean                                                               Success result
   */
  public function setPrivateKey($privateKey, $passphrase='') {
    // Read file from path
    if (is_string($privateKey) && strpos($privateKey, ' PRIVATE KEY-----') === false) {
      $privateKey = file_get_contents($privateKey);
    }

    // Validate and extract private key
    if (empty($privateKey)) {
      return false;
    }
    $privateKey = openssl_pkey_get_private($privateKey, $passphrase);
    if ($privateKey === false) {
      return false;
    }

    // Set private key
    $this->privateKey = $privateKey;
    return true;
  }


  /**
   * Load public chain and private key from PKCS#12 Certificate Store
   *
   * @param  string  $certificateStore PKCS#12 bytes or filepath
   * @param  string  $passphrase       Password for unlocking the PKCS#12 file
   * @return boolean                   Success result
   */
  public function loadPkcs12($certificateStore, $passphrase) {
    // Read file from path
    // (look for "1.2.840.113549.1.7.1" ASN.1 object identifier)
    if (strpos($certificateStore, "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x07\x01") === false) {
      $certificateStore = file_get_contents($certificateStore);
    }

    // Validate and parse certificate store
    if (empty($certificateStore)) {
      return false;
    }
    if (!openssl_pkcs12_read($certificateStore, $parsed, $passphrase)) {
      return false;
    }

    // Set public chain and private key
    $this->publicChain = [];
    $this->publicChain[] = $parsed['cert'];
    if (!empty($parsed['extracerts'])) {
      $this->publicChain = array_merge($this->publicChain, $parsed['extracerts']);
    }
    $this->privateKey = openssl_pkey_get_private($parsed['pkey']);
    return true;
  }

}
