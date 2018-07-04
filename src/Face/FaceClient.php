<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Common\XmlTools;

class FaceClient extends SoapClient {

  private static $PROD_URL = "https://webservice.face.gob.es/facturasspp2";
  private static $STAGING_URL = "https://se-face-webservice.redsara.es/facturasspp2";
  private static $WEB_NS = "https://webservice.face.gob.es";


  /**
   * Get endpoint URL
   * @return string Endpoint URL
   */
  protected function getEndpointUrl() {
    return $this->production ? self::$PROD_URL : self::$STAGING_URL;
  }


  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected function getWebNamespace() {
    return self::$WEB_NS;
  }


  /**
   * Get invoice status codes
   * @return SimpleXMLElement Response
   */
  public function getStatus() {
    return $this->request('<web:consultarEstados></web:consultarEstados>');
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


  /**
   * Get invoice
   * @param  string|string[]  $regId Invoice register ID(s)
   * @return SimpleXMLElement        Response
   */
  public function getInvoices($regId) {
    if (is_string($regId)) {
      return $this->request('<web:consultarFactura>' .
        '<numeroRegistro>' . $regId . '</numeroRegistro>' .
        '</web:consultarFactura>');
    }
    $req = '<web:consultarListadoFacturas><request>';
    foreach ($regId as $id) $req .= '<numeroRegistro>' . $id . '</numeroRegistro>';
    $req .= '</request></web:consultarListadoFacturas>';
    return $this->request($req);
  }


  /**
   * Send invoice
   * @param  string           $email       Email address
   * @param  FacturaeFile     $invoice     Invoice
   * @param  FacturaeFile[]   $attachments Attachments
   * @return SimpleXMLElement              Response
   */
  public function sendInvoice($email, $invoice, $attachments=array()) {
    $tools = new XmlTools();
    $req = '<web:enviarFactura><request>';
    $req .= '<correo>' . $email . '</correo>';
    $req .= '<factura>' .
        '<factura>' . $tools->toBase64($invoice->getData()) . '</factura>' .
        '<nombre>' . $invoice->getFilename() . '</nombre>' .
        '<mime>application/xml</mime>' . // Mandatory MIME type
      '</factura>';
    $req .= '<anexos>';
    foreach ($attachments as $file) {
      $req .= '<anexo>' .
          '<anexo>' . $tools->toBase64($file->getData()) . '</anexo>' .
          '<nombre>' . $file->getFilename() . '</nombre>' .
          '<mime>' . $file->getMimeType() . '</mime>' .
        '</anexo>';
    }
    $req .= '</anexos>';
    $req .= '</request></web:enviarFactura>';
    return $this->request($req);
  }


  /**
   * Cancel invoice
   * @param  string           $regId  Invoice register ID
   * @param  string           $reason Cancelation reason
   * @return SimpleXMLElement         Response
   */
  public function cancelInvoice($regId, $reason) {
    return $this->request('<web:anularFactura>' .
      '<numeroRegistro>' . $regId . '</numeroRegistro>' .
      '<motivo>' . $reason . '</motivo>' .
      '</web:anularFactura>');
  }

}
