<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Common\XmlTools;

/**
 * This class extends the basic functionalities of
 * @link{josemmo\Facturae\Face\SoapClient} by adding common methods between
 * FACe and FACeB2B endpoints.
 */
abstract class FaceAbstractClient extends SoapClient {

  /**
   * Get production endpoint
   * @return string Production endpoint
   */
  protected abstract function getProductionEndpoint();


  /**
   * Get staging endpoint
   * @return string Staging endpoint
   */
  protected abstract function getStagingEndpoint();


  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected abstract function getWebNamespace();


  /**
   * Do SOAP request
   * @param  string           $body Request body
   * @param  string           $ws   Web Service
   * @return SimpleXMLElement       Response
   */
  protected function request($body, $ws="facturasspp2") {
    $endpointUrlBase = $this->production ?
      $this->getProductionEndpoint() :
      $this->getStagingEndpoint();
    $endpointUrl = $endpointUrlBase . $ws;
    return parent::sendRequest($body, $endpointUrl, $this->getWebNamespace());
  }


  /**
   * Get invoice status codes
   * @return SimpleXMLElement Response
   */
  public function getStatus() {
    return $this->request('<web:consultarEstados></web:consultarEstados>');
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
        '<mime>' . $invoice->getMimeType() . '</mime>' .
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
