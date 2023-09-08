<?php
namespace josemmo\Facturae\Face\Traits;

use josemmo\Facturae\Common\XmlTools;
use josemmo\Facturae\FacturaeFile;
use SimpleXMLElement;

trait FaceTrait {
  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected function getWebNamespace() {
    return "https://webservice.face.gob.es";
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
    $req = '<web:enviarFactura><request>';
    $req .= '<correo>' . $email . '</correo>';
    $req .= '<factura>' .
        '<factura>' . XmlTools::toBase64($invoice->getData()) . '</factura>' .
        '<nombre>' . $invoice->getFilename() . '</nombre>' .
        '<mime>application/xml</mime>' . // Mandatory MIME type
      '</factura>';
    $req .= '<anexos>';
    foreach ($attachments as $file) {
      $req .= '<anexo>' .
          '<anexo>' . XmlTools::toBase64($file->getData()) . '</anexo>' .
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
