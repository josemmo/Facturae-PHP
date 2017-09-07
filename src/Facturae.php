<?php

namespace josemmo\Facturae;

/**
 * Facturae
 *
 * This file contains everything you need to create invoices.
 *
 * @package josemmo\Facturae
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/mit-license.php  MIT License
 * @author  josemmo
 */


/**
 * Facturae
 *
 * Standalone class designed to create full compliance Facturae files from
 * scratch, without the need of any other tools for signing.
 */
class Facturae {

  /* CONSTANTS */
  const SCHEMA_3_2_1 = "3.2.1";
  const TAX_IVA = 1;
  const PAYMENT_CASH = "01";
  const PAYMENT_TRANSFER = "04";
  const SIGN_POLICY_3_1 = array(
    "name" => "Política de Firma FacturaE v3.1",
    "url" => "http://www.facturae.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf",
    "digest" => "Ohixl6upD6av8N7pEvDABhEL6hM="
  );


  /* ATTRIBUTES */
  private $currency = "EUR";
  private $itemsPrecision = 6;
  private $itemsPadding = 6;
  private $totalsPrecision = 2;
  private $totalsPadding = 2;

  private $version = NULL;
  private $header = array(
    "serie" => NULL,
    "number" => NULL,
    "issueDate" => NULL,
    "dueDate" => NULL,
    "startDate" => NULL,
    "endDate" => NULL,
    "paymentMethod" => NULL,
    "paymentIBAN" => NULL
  );
  private $parties = array(
    "seller" => NULL,
    "buyer" => NULL
  );
  private $items = array();

  private $signTime = NULL;
  private $signPolicy = NULL;
  private $publicKey = NULL;
  private $privateKey = NULL;


  /**
   * Construct
   *
   * @param string $schemaVersion If omitted, latest version available
   */
  public function __construct($schemaVersion=self::SCHEMA_3_2_1) {
    $this->setSchemaVersion($schemaVersion);
  }


  /**
   * Generate random ID
   *
   * This method is used for generating random IDs required when signing the
   * document.
   *
   * @return int  Random number
   */
  private function random() {
    return rand(100000, 999999);
  }


  /**
   * Pad
   * @param  float  $val       Input
   * @param  int    $precision Decimals to round
   * @param  int    $padding   Decimals to pad
   * @return string            Padded value
   */
  private function pad($val, $precision, $padding) {
    return number_format(round($val, $precision), $padding, ".", "");
  }


  /**
   * Pad total value
   * @param  float $val Input
   * @return string     Padded value
   */
  private function padTotal($val) {
    return $this->pad($val, $this->totalsPrecision, $this->totalsPadding);
  }


  /**
   * Pad item value
   * @param  float $val Input
   * @return string     Padded value
   */
  private function padItem($val) {
    return $this->pad($val, $this->itemsPrecision, $this->itemsPadding);
  }


  /**
   * Set schema version
   *
   * @param string $schemaVersion Facturae schema version to use
   */
  public function setSchemaVersion($schemaVersion) {
    $this->version = $schemaVersion;
  }


  /**
   * Set seller
   *
   * @param FacturaeParty $seller Seller information
   */
  public function setSeller($seller) {
    $this->parties['seller'] = $seller;
  }


  /**
   * Set buyer
   *
   * @param FacturaeParty $buyer Buyer information
   */
  public function setBuyer($buyer) {
    $this->parties['buyer'] = $buyer;
  }


  /**
   * Set invoice number
   *
   * @param string     $serie  Serie code of the invoice
   * @param int|string $number Invoice number in given serie
   */
  public function setNumber($serie, $number) {
    $this->header['serie'] = $serie;
    $this->header['number'] = $number;
  }


  /**
   * Set issue date
   *
   * @param int|string $date Issue date
   */
  public function setIssueDate($date) {
    $this->header['issueDate'] = is_string($date) ? strtotime($date) : $date;
  }


  /**
   * Set due date
   *
   * @param int|string $date Due date
   */
  public function setDueDate($date) {
    $this->header['dueDate'] = is_string($date) ? strtotime($date) : $date;
  }


  /**
   * Set billing period
   *
   * @param int|string $date Start date
   * @param int|string $date End date
   */
  public function setBillingPeriod($startDate=NULL, $endDate=NULL) {
    $d_start = is_string($startDate) ? strtotime($startDate) : $startDate;
    $d_end = is_string($endDate) ? strtotime($endDate) : $endDate;
    $this->header['startDate'] = $d_start;
    $this->header['endDate'] = $d_end;
  }


  /**
   * Set dates
   *
   * This is a shortcut for setting both issue and due date in a single line
   *
   * @param int|string $issueDate Issue date
   * @param int|string $dueDate Due date
   */
  public function setDates($issueDate, $dueDate=NULL) {
    $this->setIssueDate($issueDate);
    $this->setDueDate($dueDate);
  }


  /**
   * Set payment method
   * @param string $method Payment method
   * @param string $iban   Bank account in case of bank transfer
   */
  public function setPaymentMethod($method=self::PAYMENT_CASH, $iban=NULL) {
    $this->header['paymentMethod'] = $method;
    if (!is_null($iban)) $iban = str_replace(" ", "", $iban);
    $this->header['paymentIBAN'] = $iban;
  }


  /**
   * Add item
   *
   * Adds an item row to invoice. The fist parameter ($desc), can be an string
   * representing the item description or a 2 element array containing the item
   * description and an additional string of information.
   *
   * @param string|array $desc      Item description
   * @param float        $unitPrice Price per unit, taxes included
   * @param float        $quantity  Quantity
   * @param int          $taxType   Tax type
   * @param float        $taxRate   Tax rate
   */
  public function addItem($desc, $unitPrice, $quantity=1, $taxType=NULL, $taxRate=NULL) {
    $unitPriceWithoutTax = $unitPrice;
    if ($taxType == self::TAX_IVA) $unitPriceWithoutTax /= 1 + ($taxRate / 100);
    $totalPriceWithoutTax = $unitPriceWithoutTax * $quantity;
    $totalPrice = $unitPrice * $quantity;
    $taxAmount = $totalPrice - $totalPriceWithoutTax;
    array_push($this->items, array(
      "description" => is_array($desc) ? $desc[0] : $desc,
      "additionalInformation" => is_array($desc) ? $desc[1] : NULL,
      "unitPrice" => $unitPrice,
      "unitPriceWithoutTax" => $unitPriceWithoutTax,
      "totalPrice" => $totalPrice,
      "totalPriceWithoutTax" => $totalPriceWithoutTax,
      "quantity" => $quantity,
      "taxType" => $taxType,
      "taxRate" => $taxRate,
      "taxAmount" => $taxAmount
    ));
  }


  /**
   * Get totals
   * @return array Invoice totals
   */
  public function getTotals() {
    // Define starting values
    $totals = array("withTaxes"=>0, "withoutTaxes"=>0, "taxes"=>array());

    // Run through each item
    foreach ($this->items as $item) {
      $totals['withTaxes'] += $item['totalPrice'];
      $totals['withoutTaxes'] += $item['totalPriceWithoutTax'];
      if ($item['taxType'] == self::TAX_IVA) {
        if (!isset($totals['taxes'][$item['taxRate']]))
          $totals['taxes'][$item['taxRate']] = array("base"=>0, "amount"=>0);
        $totals['taxes'][$item['taxRate']]['base'] += $item['totalPriceWithoutTax'];
        $totals['taxes'][$item['taxRate']]['amount'] += $item['taxAmount'];
      }
    }

    // Get only taxes value
    $totals['onlyTaxes'] = $totals['withTaxes'] - $totals['withoutTaxes'];

    // Return data
    return $totals;
  }


  /**
   * Set sign time
   * @param int|string $time Time of the signature
   */
  public function setSignTime($time) {
    $this->signTime = is_string($time) ? strtotime($time) : $time;
  }


  /**
   * Sign
   *
   * @param  string $publicPath  Path to public key PEM file
   * @param  string $privatePath Path to private key PEM file
   * @param  string $passphrase  Private key passphrase
   * @param  array $policy       Facturae sign policy
   */
  public function sign($publicPath, $privatePath, $passphrase, $policy=self::SIGN_POLICY_3_1) {
    $this->publicKey = openssl_x509_read(file_get_contents($publicPath));
    $this->privateKey = openssl_pkey_get_private(
      file_get_contents($privatePath), $passphrase);
    $this->signPolicy = $policy;

    // Generate random IDs
    $this->signatureID = $this->random();
    $this->signedInfoID = $this->random();
    $this->signedPropertiesID = $this->random();
    $this->signatureValueID = $this->random();
    $this->certificateID = $this->random();
    $this->referenceID = $this->random();
    $this->signatureSignedPropertiesID = $this->random();
    $this->signatureObjectID = $this->random();
  }


  /**
   * Inject signature
   * @param  string Unsigned XML document
   * @return string Signed XML document
   */
  private function injectSignature($xml) {
    // Make sure we have all we need to sign the document
    if (is_null($this->publicKey) || is_null($this->privateKey)) return $xml;

    // Normalize document
    $xml = str_replace("\r", "", $xml);

    // Define namespace
    $xmlns = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ' .
      'xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#" ' .
      'xmlns:fe="http://www.facturae.es/Facturae/2014/v3.2.1/Facturae"';

    // Prepare signed properties
    $signTime = is_null($this->signTime) ? time() : $this->signTime;
    $certData = openssl_x509_parse($this->publicKey);
    $certDigest = openssl_x509_fingerprint($this->publicKey, "sha1", true);
    $certDigest = base64_encode($certDigest);
    $certIssuer = "CN=" . $certData['issuer']['CN'] . "," .
                  "OU=" . $certData['issuer']['OU'] . "," .
                  "O=" .  $certData['issuer']['O']  . "," .
                  "C=" .  $certData['issuer']['C'];
    $prop = '<etsi:SignedProperties Id="Signature' . $this->signatureID .
      '-SignedProperties' . $this->signatureSignedPropertiesID . '">' .
      '<etsi:SignedSignatureProperties><etsi:SigningTime>' .
      date('c', $signTime) . '</etsi:SigningTime>' .
      '<etsi:SigningCertificate><etsi:Cert><etsi:CertDigest>' .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod><ds:DigestValue>' . $certDigest .
      '</ds:DigestValue></etsi:CertDigest><etsi:IssuerSerial>' .
      '<ds:X509IssuerName>' . $certIssuer .
      '</ds:X509IssuerName><ds:X509SerialNumber>' .
      $certData['serialNumber'] . '</ds:X509SerialNumber>' .
      '</etsi:IssuerSerial></etsi:Cert></etsi:SigningCertificate>' .
      '<etsi:SignaturePolicyIdentifier><etsi:SignaturePolicyId>' .
      '<etsi:SigPolicyId><etsi:Identifier>' . $this->signPolicy['url'] .
      '</etsi:Identifier><etsi:Description>' . $this->signPolicy['name'] .
      '</etsi:Description></etsi:SigPolicyId><etsi:SigPolicyHash>' .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod><ds:DigestValue>' . $this->signPolicy['digest'] .
      '</ds:DigestValue></etsi:SigPolicyHash></etsi:SignaturePolicyId>' .
      '</etsi:SignaturePolicyIdentifier><etsi:SignerRole>' .
      '<etsi:ClaimedRoles><etsi:ClaimedRole>emisor</etsi:ClaimedRole>' .
      '</etsi:ClaimedRoles></etsi:SignerRole>' .
      '</etsi:SignedSignatureProperties><etsi:SignedDataObjectProperties>' .
      '<etsi:DataObjectFormat ObjectReference="#Reference-ID-' .
      $this->referenceID . '"><etsi:Description>Factura electrónica' .
      '</etsi:Description><etsi:MimeType>text/xml</etsi:MimeType>' .
      '</etsi:DataObjectFormat></etsi:SignedDataObjectProperties>' .
      '</etsi:SignedProperties>';

    // Prepare key info
    $kInfo = '<ds:KeyInfo Id="Certificate' . $this->certificateID . '">' .
      "\n" . '<ds:X509Data>' . "\n" . '<ds:X509Certificate>' . "\n";
    $publicPEM = "";
    openssl_x509_export($this->publicKey, $publicPEM);
    $publicPEM = str_replace("-----BEGIN CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("-----END CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("\n", "", $publicPEM);
    $publicPEM = str_replace("\r", "", chunk_split($publicPEM, 76));
    $kInfo .= $publicPEM . '</ds:X509Certificate>' . "\n" . '</ds:X509Data>' .
      "\n" . '<ds:KeyValue>' . "\n" . '<ds:RSAKeyValue>' . "\n" .
      '<ds:Modulus>' . "\n";
    $privateData = openssl_pkey_get_details($this->privateKey);
    $modulus = chunk_split(base64_encode($privateData['rsa']['n']), 76);
    $modulus = str_replace("\r", "", $modulus);
    $exponent = base64_encode($privateData['rsa']['e']);
    $kInfo .= $modulus . '</ds:Modulus>' . "\n" . '<ds:Exponent>' . $exponent .
      '</ds:Exponent>' . "\n" . '</ds:RSAKeyValue>' . "\n" . '</ds:KeyValue>' .
      "\n" . '</ds:KeyInfo>';

    // Calculate digests
    $propDigest = base64_encode(sha1(str_replace('<etsi:SignedProperties',
      '<etsi:SignedProperties ' . $xmlns, $prop), true));
    $kInfoDigest = base64_encode(sha1(str_replace('<ds:KeyInfo',
      '<ds:KeyInfo ' . $xmlns, $kInfo), true));
    $documentDigest = base64_encode(sha1($xml, true));

    // Prepare signed info
    $sInfo = '<ds:SignedInfo Id="Signature-SignedInfo' . $this->signedInfoID .
      '">' . "\n" . '<ds:CanonicalizationMethod Algorithm="' .
      'http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
      '</ds:CanonicalizationMethod>' . "\n" . '<ds:SignatureMethod ' .
      'Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1">' .
      '</ds:SignatureMethod>' . "\n" . '<ds:Reference Id="SignedPropertiesID' .
      $this->signedPropertiesID . '" ' .
      'Type="http://uri.etsi.org/01903#SignedProperties" ' .
      'URI="#Signature' . $this->signatureID . '-SignedProperties' .
      $this->signatureSignedPropertiesID . '">' . "\n" . '<ds:DigestMethod ' .
      'Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod>' . "\n" . '<ds:DigestValue>' . $propDigest .
      '</ds:DigestValue>' . "\n" . '</ds:Reference>' . "\n" . '<ds:Reference ' .
      'URI="#Certificate' . $this->certificateID . '">' . "\n" .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod>' . "\n" . '<ds:DigestValue>' . $kInfoDigest .
      '</ds:DigestValue>' . "\n" . '</ds:Reference>' . "\n" .
      '<ds:Reference Id="Reference-ID-' . $this->referenceID . '" URI="">' .
      "\n" . '<ds:Transforms>' . "\n" . '<ds:Transform Algorithm="' .
      'http://www.w3.org/2000/09/xmldsig#enveloped-signature">' .
      '</ds:Transform>' . "\n" . '</ds:Transforms>' . "\n" .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod>' . "\n" . '<ds:DigestValue>' . $documentDigest .
      '</ds:DigestValue>' . "\n" . '</ds:Reference>' . "\n" .'</ds:SignedInfo>';

    // Calculate signature
    $signaturePayload = str_replace('<ds:SignedInfo',
      '<ds:SignedInfo ' . $xmlns, $sInfo);
    $signatureResult = "";
    openssl_sign($signaturePayload, $signatureResult, $this->privateKey);
    $signatureResult = chunk_split(base64_encode($signatureResult), 76);
    $signatureResult = str_replace("\r", "", $signatureResult);

    // Make signature
    $sig = '<ds:Signature xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#" ' .
      'Id="Signature' . $this->signatureID . '">' . "\n" . $sInfo . "\n" .
      '<ds:SignatureValue Id="SignatureValue' . $this->signatureValueID . '">' .
      "\n" . $signatureResult . '</ds:SignatureValue>' . "\n" . $kInfo . "\n" .
      '<ds:Object Id="Signature' . $this->signatureID . '-Object' .
      $this->signatureObjectID . '"><etsi:QualifyingProperties ' .
      'Target="#Signature' . $this->signatureID . '">' . $prop .
      '</etsi:QualifyingProperties></ds:Object></ds:Signature>';

    // Inject signature
    $xml = str_replace('</fe:Facturae>', $sig . '</fe:Facturae>', $xml);

    return $xml;
  }


  /**
   * Export
   *
   * Get Facturae XML data
   *
   * @param  string     $filePath Path to save invoice
   * @return string|int           XML data|Written file bytes
   */
  public function export($filePath=NULL) {
    // Prepare document
    $xml = '<fe:Facturae xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ' .
           'xmlns:fe="http://www.facturae.es/Facturae/2014/v' .
           $this->version . '/Facturae">';
    $totals = $this->getTotals();

    // Add header
    $batchIdentifier = $this->parties['seller']->taxNumber .
      $this->header['number'] . $this->header['serie'];
    $xml .= '<FileHeader><SchemaVersion>' . $this->version .'</SchemaVersion>' .
      '<Modality>I</Modality><InvoiceIssuerType>EM</InvoiceIssuerType>' .
      '<Batch><BatchIdentifier>' . $batchIdentifier . '</BatchIdentifier>' .
      '<InvoicesCount>1</InvoicesCount><TotalInvoicesAmount><TotalAmount>' .
      $this->padTotal($totals['withTaxes']) . '</TotalAmount>' .
      '</TotalInvoicesAmount><TotalOutstandingAmount><TotalAmount>' .
      $this->padTotal($totals['withTaxes']) . '</TotalAmount>' .
      '</TotalOutstandingAmount><TotalExecutableAmount>' .
      '<TotalAmount>' . $this->padTotal($totals['withTaxes']) .
      '</TotalAmount></TotalExecutableAmount><InvoiceCurrencyCode>' .
      $this->currency . '</InvoiceCurrencyCode></Batch></FileHeader>';

    // Add parties
    $xml .= '<Parties><SellerParty>' .
      $this->parties['seller']->getXML($this->version) . '</SellerParty>' .
      '<BuyerParty>' . $this->parties['buyer']->getXML($this->version) .
      '</BuyerParty></Parties>';

    // Add invoice data
    $xml .= '<Invoices><Invoice><InvoiceHeader><InvoiceNumber>' .
      $this->header['number'] . '</InvoiceNumber><InvoiceSeriesCode>' .
      $this->header['serie'] . '</InvoiceSeriesCode><InvoiceDocumentType>' .
      'FC</InvoiceDocumentType><InvoiceClass>OO</InvoiceClass>' .
      '</InvoiceHeader><InvoiceIssueData><IssueDate>' .
      date('Y-m-d', $this->header['issueDate']) . '</IssueDate>';
    if (!is_null($this->header['startDate'])) {
      $xml .= '<InvoicingPeriod><StartDate>' .
        date('Y-m-d', $this->header['startDate']) . '</StartDate><EndDate>' .
        date('Y-m-d', $this->header['endDate']) . '</EndDate>' .
        '</InvoicingPeriod>';
    }
    $xml .= '<InvoiceCurrencyCode>' . $this->currency .
      '</InvoiceCurrencyCode><TaxCurrencyCode>' . $this->currency .
      '</TaxCurrencyCode><LanguageName>es</LanguageName></InvoiceIssueData>';

    // Add invoice taxes
    if (count($totals['taxes']) > 0) {
      $xml .= '<TaxesOutputs>';
      foreach ($totals['taxes'] as $rate=>$tax) {
        $xml .= '<Tax><TaxTypeCode>01</TaxTypeCode><TaxRate>' . $rate .
          '</TaxRate><TaxableBase><TotalAmount>' .
          $this->padTotal($tax['base']) . '</TotalAmount></TaxableBase>' .
          '<TaxAmount><TotalAmount>' . $this->padTotal($tax['amount']) .
          '</TotalAmount></TaxAmount></Tax>';
      }
      $xml .= '</TaxesOutputs>';
    }

    // Add invoice totals
    $xml .= '<InvoiceTotals><TotalGrossAmount>' .
      $this->padTotal($totals['withoutTaxes']) . '</TotalGrossAmount>' .
      '<TotalGeneralDiscounts>0.00</TotalGeneralDiscounts>' .
      '<TotalGeneralSurcharges>0.00</TotalGeneralSurcharges>' .
      '<TotalGrossAmountBeforeTaxes>' .
      $this->padTotal($totals['withoutTaxes']) .
      '</TotalGrossAmountBeforeTaxes><TotalTaxOutputs>' .
      $this->padTotal($totals['onlyTaxes']) .
      '</TotalTaxOutputs><TotalTaxesWithheld>0.00</TotalTaxesWithheld>' .
      '<InvoiceTotal>' . $this->padTotal($totals['withTaxes']) .
      '</InvoiceTotal><TotalOutstandingAmount>' .
      $this->padTotal($totals['withTaxes']) . '</TotalOutstandingAmount>' .
      '<TotalExecutableAmount>' . $this->padTotal($totals['withTaxes']) .
      '</TotalExecutableAmount></InvoiceTotals>';

    // Add invoice items
    $xml .= '<Items>';
    foreach ($this->items as $item) {
      $xml .= '<InvoiceLine><ItemDescription>' . $item['description'] .
        '</ItemDescription><Quantity>' . $this->padTotal($item['quantity']) .
        '</Quantity><UnitOfMeasure>01</UnitOfMeasure><UnitPriceWithoutTax>' .
        $this->padItem($item['unitPriceWithoutTax']) .
        '</UnitPriceWithoutTax><TotalCost>' .
        $this->padTotal($item['totalPriceWithoutTax']) . '</TotalCost>' .
        '<GrossAmount>' . $this->padTotal($item['totalPriceWithoutTax']) .
        '</GrossAmount>';
      if ($item['taxType'] == self::TAX_IVA) {
        $xml .= '<TaxesOutputs><Tax><TaxTypeCode>01</TaxTypeCode><TaxRate>' .
        $item['taxRate'] . '</TaxRate><TaxableBase><TotalAmount>' .
        $this->padTotal($item['totalPriceWithoutTax']) . '</TotalAmount>' .
        '</TaxableBase><TaxAmount><TotalAmount>' .
        $this->padTotal($item['taxAmount']) . '</TotalAmount></TaxAmount>' .
        '</Tax></TaxesOutputs>';
      }
      if (!is_null($item['additionalInformation'])) {
        $xml .= '<AdditionalLineItemInformation>' .
          $item['additionalInformation'] . '</AdditionalLineItemInformation>';
      }
      $xml .= '</InvoiceLine>';
    }
    $xml .= '</Items>';

    // Add payment details
    if (!is_null($this->header['paymentMethod'])) {
      $xml .= '<PaymentDetails><Installment><InstallmentDueDate>' .
        date('Y-m-d', is_null($this->header['dueDate']) ?
          $this->header['issueDate'] : $this->header['dueDate']) .
        '</InstallmentDueDate><InstallmentAmount>' .
        $this->padTotal($totals['withTaxes']) . '</InstallmentAmount>' .
        '<PaymentMeans>' . $this->header['paymentMethod'] . '</PaymentMeans>';
      if ($this->header['paymentMethod'] == self::PAYMENT_TRANSFER) {
        $xml .= '<AccountToBeCredited><IBAN>' . $this->header['paymentIBAN'] .
          '</IBAN></AccountToBeCredited>';
      }
      $xml .= '</Installment></PaymentDetails>';
    }

    // Close invoice and document
    $xml .= '</Invoice></Invoices></fe:Facturae>';

    // Add signature
    $xml = $this->injectSignature($xml);

    // Prepend content type
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;

    // Save document
    if (!is_null($filePath)) return file_put_contents($filePath, $xml);
    return $xml;
  }

}
