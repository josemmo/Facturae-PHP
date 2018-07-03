<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Common\XmlTools;

class Faceb2bClient extends SoapClient {

  private static $PROD_URL = "https://ws.faceb2b.gob.es/sv1/invoice";
  private static $STAGING_URL = "https://se-ws-faceb2b.redsara.es/sv1/invoice";
  private static $WEB_NS = "https://webservice.faceb2b.gob.es";


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
   * Send invoice
   * @param  FacturaeFile     $invoice     Invoice
   * @param  FacturaeFile     $attachment  Attachment
   * @return SimpleXMLElement              Response
   */
  public function sendInvoice($invoice, $attachment=null) {
    $tools = new XmlTools();
    $req = '<web:SendInvoice><request>';

    $req .= '<invoiceFile>' .
        '<content>' . $tools->toBase64($invoice->getData()) . '</content>' .
        '<name>' . $invoice->getFilename() . '</name>' .
        '<mime>' . $invoice->getMimeType() . '</mime>' .
      '</invoiceFile>';

    if (!is_null($attachment)) {
      $req .= '<attachmentFile>' .
          '<content>' . $tools->toBase64($attachment->getData()) . '</content>' .
          '<name>' . $attachment->getFilename() . '</name>' .
          '<mime>' . $attachment->getMimeType() . '</mime>' .
        '</attachmentFile>';
    }

    $req .= '</request></web:SendInvoice>';
    return $this->request($req);
  }


  /**
   * Get invoice details
   * @param  string           $regId Registry number
   * @return SimpleXMLElement        Response
   */
  public function getInvoiceDetails($regId) {
    return $this->request('<web:GetInvoiceDetails><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:GetInvoiceDetails>');
  }


  /**
   * Request invoice cancellation
   * @param  string           $regId   Registry number
   * @param  string           $reason  Reason code
   * @param  string           $comment Additional comments
   * @return SimpleXMLElement          Response
   */
  public function requestInvoiceCancellation($regId, $reason, $comment=null) {
    $req = '<web:RequestInvoiceCancellation><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<reason>' . $reason . '</reason>';
    if (empty($comment)) $req .= '<comment>' . $comment . '</comment>';
    $req .= '</request></web:RequestInvoiceCancellation>';
    return $this->request($req);
  }


  /**
   * Get registered invoices
   * @param  string           $receivingUnit Receiving unit code
   * @return SimpleXMLElement                Response
   */
  public function getRegisteredInvoices($receivingUnit=null) {
    $req = '<web:GetRegisteredInvoices><request>';
    if (is_null($receivingUnit)) {
      $req .= '<receivingUnit>' . $receivingUnit . '</receivingUnit>';
    }
    $req .= '</request></web:GetRegisteredInvoices>';
    return $this->request($req);
  }


  /**
   * Get invoice cancellations
   * @return SimpleXMLElement Response
   */
  public function getInvoiceCancellations() {
    return $this->request('<web:GetInvoiceCancellations><request>' .
      '</request></web:GetInvoiceCancellations>');
  }


  /**
   * Download invoice
   * @param  string           $regId    Registry number
   * @param  boolean          $validate Validate invoice
   * @return SimpleXMLElement           Response
   */
  public function downloadInvoice($regId, $validate=true) {
    $req = '<web:DownloadInvoice><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    if ($validate) {
      $req .= '<signatureValidationMode>validate</signatureValidationMode>';
    }
    $req .= '</request></web:DownloadInvoice>';
    return $this->request($req);
  }


  /**
   * Confirm invoice download
   * @param  string           $regId   Registry number
   * @return SimpleXMLElement          Response
   */
  public function confirmInvoiceDownload($regId) {
    return $this->request('<web:ConfirmInvoiceDownload><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:ConfirmInvoiceDownload>');
  }


  /**
   * Reject invoice
   * @param  string           $regId   Registry number
   * @param  string           $reason  Reason code
   * @param  string           $comment Additional comments
   * @return SimpleXMLElement          Response
   */
  public function rejectInvoice($regId, $reason, $comment=null) {
    $req = '<web:RejectInvoice><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<reason>' . $reason . '</reason>';
    if (empty($comment)) $req .= '<comment>' . $comment . '</comment>';
    $req .= '</request></web:RejectInvoice>';
    return $this->request($req);
  }


  /**
   * Mark invoice as paid
   * @param  string           $regId   Registry number
   * @return SimpleXMLElement          Response
   */
  public function markInvoiceAsPaid($regId) {
    return $this->request('<web:MarkInvoiceAsPaid>><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:MarkInvoiceAsPaid>>');
  }


  /**
   * Accept invoice cancellation
   * @param  string           $regId   Registry number
   * @return SimpleXMLElement          Response
   */
  public function acceptInvoiceCancellation($regId) {
    return $this->request('<web:AcceptInvoiceCancellation><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:AcceptInvoiceCancellation>');
  }


  /**
   * Reject invoice cancellation
   * @param  string           $regId   Registry number
   * @param  string           $comment Commment
   * @return SimpleXMLElement          Response
   */
  public function rejectInvoiceCancellation($regId, $comment) {
    return $this->request('<web:RejectInvoiceCancellation><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '<comment>' . $comment . '</comment>' .
      '</request></web:RejectInvoiceCancellation>');
  }


  /**
   * Validate invoice signature
   * @param  string           $regId   Registry number
   * @param  FacturaeFile     $invoice Invoice
   * @return SimpleXMLElement          Response
   */
  public function validateInvoiceSignature($regId, $invoice) {
    $tools = new XmlTools();
    $req = '<web:ValidateInvoiceSignature><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<invoiceFile>' .
        '<content>' . $tools->toBase64($invoice->getData()) . '</content>' .
        '<name>' . $invoice->getFilename() . '</name>' .
        '<mime>' . $invoice->getMimeType() . '</mime>' .
      '</invoiceFile>';
    $req .= '</request></web:ValidateInvoiceSignature>';
    return $this->request($req);
  }


  /**
   * Get codes
   * @param  string           $type Code type
   * @return SimpleXMLElement       Response
   */
  public function getCodes($type="") {
    return $this->request('<web:GetCodes><request>' .
      '<codeType>' . $type . '</codeType>' .
      '</request></web:GetCodes>');
  }

}
