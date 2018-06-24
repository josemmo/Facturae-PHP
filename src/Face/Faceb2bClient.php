<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Common\XmlTools;

/**
 * This class allows communication with the FACeB2B Web Service.
 */
class Faceb2bClient extends FaceAbstractClient {

  /**
   * Get production endpoint
   * @return string Production endpoint
   */
  protected function getProductionEndpoint() {
    return "https://webservice.faceb2b.gob.es/";
  }


  /**
   * Get staging endpoint
   * @return string Staging endpoint
   */
  protected function getStagingEndpoint() {
    return "https://se-faceb2b-webservice.redsara.es/";
  }


  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected function getWebNamespace() {
    return "https://webservice.faceb2b.gob.es";
  }

}
