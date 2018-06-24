<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Common\XmlTools;

/**
 * This class allows communication with the FACe Web Service.
 */
class FaceClient extends FaceAbstractClient {

  /**
   * Get production endpoint
   * @return string Production endpoint
   */
  protected function getProductionEndpoint() {
    return "https://webservice.face.gob.es/";
  }


  /**
   * Get staging endpoint
   * @return string Staging endpoint
   */
  protected function getStagingEndpoint() {
    return "https://se-face-webservice.redsara.es/";
  }


  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected function getWebNamespace() {
    return "https://webservice.face.gob.es";
  }


  /**
   * Get administrations
   * @param  boolean          $onlyTopLevel Get only top level administrations
   * @return SimpleXMLElement               Response
   */
  public function getAdministrations($onlyTopLevel=true) {
    $tag = "consultarAdministraciones";
    if (!$onlyTopLevel) $tag .= "Repositorio";
    return $this->request("<web:$tag></web:$tag>");
  }


  /**
   * Get units
   * @param  string|null      $code Administration code
   * @return SimpleXMLElement       Response
   */
  public function getUnits($code=null) {
    if (is_null($code)) return $this->request('<web:consultarUnidades></web:consultarUnidades>');
    return $this->request('<web:consultarUnidadesPorAdministracion>' .
      '<codigoDir>' . $code . '</codigoDir>' .
      '</web:consultarUnidadesPorAdministracion>');
  }


  /**
   * Get NIFs
   * @param  string|null      $code Administration code
   * @return SimpleXMLElement       Response
   */
  public function getNifs($code=null) {
    if (is_null($code)) return $this->request('<web:consultarNIFs></web:consultarNIFs>');
    return $this->request('<web:consultarNIFsPorAdministracion>' .
      '<codigoDir>' . $code . '</codigoDir>' .
      '</web:consultarNIFsPorAdministracion>');
  }

}
