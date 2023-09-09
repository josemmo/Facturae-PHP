<?php
namespace josemmo\Facturae\Face\Traits;

use josemmo\Facturae\Common\XmlTools;
use josemmo\Facturae\FacturaeFile;
use SimpleXMLElement;

trait Faceb2bTrait {
  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected function getWebNamespace() {
    return "https://webservice.faceb2b.gob.es";
  }


  /**
   * Send invoice
   * @param  FacturaeFile      $invoice    Invoice
   * @param  FacturaeFile|null $attachment Optional attachment
   * @return SimpleXMLElement              Response
   */
  public function sendInvoice($invoice, $attachment=null) {
    $req = '<web:SendInvoice><request>';

    $req .= '<invoiceFile>' .
        '<content>' . XmlTools::toBase64($invoice->getData()) . '</content>' .
        '<name>' . $invoice->getFilename() . '</name>' .
        '<mime>text/xml</mime>' . // Mandatory MIME type
      '</invoiceFile>';

    if (!is_null($attachment)) {
      $req .= '<attachmentFile>' .
          '<content>' . XmlTools::toBase64($attachment->getData()) . '</content>' .
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
   * @param  string|null      $comment Optional comments
   * @return SimpleXMLElement          Response
   */
  public function requestInvoiceCancellation($regId, $reason, $comment=null) {
    $req = '<web:RequestInvoiceCancellation><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<reason>' . $reason . '</reason>';
    if (!is_null($comment)) {
      $req .= '<comment>' . $comment . '</comment>';
    }
    $req .= '</request></web:RequestInvoiceCancellation>';
    return $this->request($req);
  }


  /**
   * Get registered invoices
   * @param  string|null      $receivingUnit Receiving unit code
   * @return SimpleXMLElement                Response
   */
  public function getRegisteredInvoices($receivingUnit=null) {
    $req = '<web:GetRegisteredInvoices><request>';
    if (!is_null($receivingUnit)) {
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
   * @param  string           $regId Registry number
   * @return SimpleXMLElement        Response
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
   * @param  string|null      $comment Optional comments
   * @return SimpleXMLElement          Response
   */
  public function rejectInvoice($regId, $reason, $comment=null) {
    $req = '<web:RejectInvoice><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<reason>' . $reason . '</reason>';
    if (!is_null($comment)) {
      $req .= '<comment>' . $comment . '</comment>';
    }
    $req .= '</request></web:RejectInvoice>';
    return $this->request($req);
  }


  /**
   * Mark invoice as paid
   * @param  string           $regId Registry number
   * @return SimpleXMLElement        Response
   */
  public function markInvoiceAsPaid($regId) {
    return $this->request('<web:MarkInvoiceAsPaid><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:MarkInvoiceAsPaid>');
  }


  /**
   * Accept invoice cancellation
   * @param  string           $regId Registry number
   * @return SimpleXMLElement        Response
   */
  public function acceptInvoiceCancellation($regId) {
    return $this->request('<web:AcceptInvoiceCancellation><request>' .
      '<registryNumber>' . $regId . '</registryNumber>' .
      '</request></web:AcceptInvoiceCancellation>');
  }


  /**
   * Reject invoice cancellation
   * @param  string           $regId   Registry number
   * @param  string           $comment Comment
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
    $req = '<web:ValidateInvoiceSignature><request>';
    $req .= '<registryNumber>' . $regId . '</registryNumber>';
    $req .= '<invoiceFile>' .
        '<content>' . XmlTools::toBase64($invoice->getData()) . '</content>' .
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
