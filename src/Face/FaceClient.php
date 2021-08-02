<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Face\Traits\FaceTrait;
use josemmo\Facturae\Face\Traits\StageableTrait;

class FaceClient extends SoapClient {
  use StageableTrait;
  use FaceTrait;

  /**
   * Get endpoint URL
   * @return string Endpoint URL
   */
  protected function getEndpointUrl() {
    return $this->isProduction() ?
      "https://webservice.face.gob.es/facturasspp2" :
      "https://se-face-webservice.redsara.es/facturasspp2";
  }
}
