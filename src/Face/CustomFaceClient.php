<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Face\Traits\FaceTrait;

class CustomFaceClient extends SoapClient {
  private $endpointUrl;
  private $webNamespace = "https://webservice.face.gob.es";

  use FaceTrait;

  /**
   * CustomFaceClient constructor
   *
   * @param string $endpointUrl FACe Web Service endpoint URL
   * @param string $publicPath  Path to public key in PEM or PKCS#12 file
   * @param string $privatePath Path to private key (null for PKCS#12)
   * @param string $passphrase  Private key passphrase
   */
  public function __construct($endpointUrl, $publicPath, $privatePath=null, $passphrase="") {
    parent::__construct($publicPath, $privatePath, $passphrase);
    $this->endpointUrl = $endpointUrl;
  }


  /**
   * Set custom web service namespace
   * @param string $webNamespace Web service namespace to override the default one
   */
  public function setWebNamespace($webNamespace) {
    $this->webNamespace = $webNamespace;
  }


  /**
   * @inheritdoc
   */
  protected function getWebNamespace() {
    return $this->webNamespace;
  }


  /**
   * Get endpoint URL
   * @return string Endpoint URL
   */
  protected function getEndpointUrl() {
    return $this->endpointUrl;
  }
}
