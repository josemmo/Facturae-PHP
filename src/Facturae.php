<?php

namespace josemmo\Facturae;

/**
 * Facturae
 *
 * This file contains everything you need to create invoices.
 *
 * @package josemmo\Facturae
 * @version 1.1.0
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
  const SCHEMA_3_2_2 = "3.2.2";
  const SIGN_POLICY_3_1 = array(
    "name" => "Política de Firma FacturaE v3.1",
    "url" => "http://www.facturae.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf",
    "digest" => "Ohixl6upD6av8N7pEvDABhEL6hM="
  );

  const PAYMENT_CASH = "01";
  const PAYMENT_TRANSFER = "04";

  const TAX_IVA = "01";
  const TAX_IPSI = "02";
  const TAX_IGIC = "03";
  const TAX_IRPF = "04";
  const TAX_OTHER = "05";
  const TAX_ITPAJD = "06";
  const TAX_IE = "07";
  const TAX_RA = "08";
  const TAX_IGTECM = "09";
  const TAX_IECDPCAC = "10";
  const TAX_IIIMAB = "11";
  const TAX_ICIO = "12";
  const TAX_IMVDN = "13";
  const TAX_IMSN = "14";
  const TAX_IMGSN = "15";
  const TAX_IMPN = "16";
  const TAX_REIVA = "17";
  const TAX_REIGIC = "18";
  const TAX_REIPSI = "19";


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
  private $legalLiterals = array();

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
   * @return int Random number
   */
  private function random() {
    if (function_exists('random_int')) {
      return random_int(0x10000000, 0x7FFFFFFF);
    } else {
      return rand(100000, 999999);
    }
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
   * Is withheld tax
   *
   * This method returns if a tax type is, by default, a withheld tax
   *
   * @param  string  $taxCode Tax
   * @return boolean          Is withheld
   */
  public static function isWithheldTax($taxCode) {
    return in_array($taxCode, [self::TAX_IRPF]);
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
   * @param FacturaeItem|string|array $desc      Item to add or description
   * @param float                     $unitPrice Price per unit, taxes included
   * @param float                     $quantity  Quantity
   * @param int                       $taxType   Tax type
   * @param float                     $taxRate   Tax rate
   */
  public function addItem($desc, $unitPrice=NULL, $quantity=1, $taxType=NULL, $taxRate=NULL) {
    if ($desc instanceOf FacturaeItem) {
      $item = $desc;
    } else {
      $item = new FacturaeItem([
        "name" => is_array($desc) ? $desc[0] : $desc,
        "description" => is_array($desc) ? $desc[1] : NULL,
        "quantity" => $quantity,
        "unitPrice" => $unitPrice,
        "taxes" => array($taxType => $taxRate)
      ]);
    }
    array_push($this->items, $item);
  }


  /**
   * Add legal literal
   * @param string $message Legal literal reference
   */
  public function addLegalLiteral($message) {
    $this->legalLiterals[] = $message;
  }


  /**
   * Get totals
   * @return array Invoice totals
   */
  public function getTotals() {
    // Define starting values
    $totals = array(
      "taxesOutputs" => array(),
      "taxesWithheld" => array(),
      "invoiceAmount" => 0,
      "grossAmount" => 0,
      "grossAmountBeforeTaxes" => 0,
      "totalTaxesOutputs" => 0,
      "totalTaxesWithheld" => 0
    );

    // Run through every item
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData();
      $totals['invoiceAmount'] += $item['totalAmount'];
      $totals['grossAmount'] += $item['grossAmount'];
      $totals['totalTaxesOutputs'] += $item['totalTaxesOutputs'];
      $totals['totalTaxesWithheld'] += $item['totalTaxesWithheld'];

      // Get taxes
      foreach (["taxesOutputs", "taxesWithheld"] as $taxGroup) {
        foreach ($item[$taxGroup] as $type=>$tax) {
          if (!isset($totals[$taxGroup][$type]))
            $totals[$taxGroup][$type] = array();
          if (!isset($totals[$taxGroup][$type][$tax['rate']]))
            $totals[$taxGroup][$type][$tax['rate']] = array("base"=>0, "amount"=>0);
          $totals[$taxGroup][$type][$tax['rate']]['base'] +=
            $item['totalAmountWithoutTax'];
          $totals[$taxGroup][$type][$tax['rate']]['amount'] += $tax['amount'];
        }
      }
    }

    // Fill rest of values
    $totals['grossAmountBeforeTaxes'] = $totals['grossAmount'];

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
   * Load a PKCS#12 Certificate Store
   *
   * @param  string $pkcs12File  The certificate store file name
   * @param  string $pkcs12Pass  Encryption password for unlocking the PKCS#12 file
   * @return bool                Success
   */
  private function loadPkcs12($pkcs12File, $pkcs12Pass="") {
    if (!is_file($pkcs12File)) return false;

    // Extract public and private keys from store
    if (openssl_pkcs12_read(file_get_contents($pkcs12File), $certs, $pkcs12Pass)) {
      $this->publicKey = openssl_x509_read($certs['cert']);
      $this->privateKey = openssl_pkey_get_private($certs['pkey']);
    }

    return (!empty($this->publicKey) && !empty($this->privateKey));
  }


  /**
   * Load a X.509 certificate and PEM encoded private key
   *
   * @param  string $publicPath  Path to public key PEM file
   * @param  string $privatePath Path to private key PEM file
   * @param  string $passphrase  Private key passphrase
   * @return bool                Success
   */
  private function loadX509($publicPath, $privatePath, $passphrase="") {
    if (is_file($publicPath) && is_file($privatePath)) {
      $this->publicKey = openssl_x509_read(file_get_contents($publicPath));
      $this->privateKey = openssl_pkey_get_private(
        file_get_contents($privatePath),
        $passphrase
      );
    }
    return (!empty($this->publicKey) && !empty($this->privateKey));
  }


  /**
   * Sign
   *
   * @param  string $publicPath  Path to public key PEM file or PKCS#12 certificate store
   * @param  string $privatePath Path to private key PEM file (should be NULL in case of PKCS#12)
   * @param  string $passphrase  Private key passphrase
   * @param  array  $policy      Facturae sign policy
   * @return bool                Success
   */
  public function sign($publicPath, $privatePath=NULL, $passphrase="", $policy=self::SIGN_POLICY_3_1) {
    $this->publicKey = NULL;
    $this->privateKey = NULL;
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

    // Load public and private keys
    if (empty($privatePath)) {
      return $this->loadPkcs12($publicPath, $passphrase);
    } else {
      return $this->loadX509($publicPath, $privatePath, $passphrase);
    }
  }


  /**
   * Inject signature
   * @param  string Unsigned XML document
   * @return string Signed XML document
   */
  private function injectSignature($xml) {
    // Make sure we have all we need to sign the document
    if (empty($this->publicKey) || empty($this->privateKey)) return $xml;

    // Normalize document
    $xml = str_replace("\r", "", $xml);

    // Define namespace
    $xmlns = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ' .
      'xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" ' .
      'xmlns:fe="http://www.facturae.es/Facturae/2014/v' .
      $this->version . '/Facturae"';

    // Prepare signed properties
    $signTime = is_null($this->signTime) ? time() : $this->signTime;
    $certData = openssl_x509_parse($this->publicKey);
    $certDigest = openssl_x509_fingerprint($this->publicKey, "sha1", true);
    $certDigest = base64_encode($certDigest);
    foreach ($certData['issuer'] as $item=>$value) {
      $certIssuer[] = $item . '=' . $value;
    }
    $certIssuer = implode(',', $certIssuer);

    // Generate signed properties
    $prop = '<xades:SignedProperties Id="Signature' . $this->signatureID .
      '-SignedProperties' . $this->signatureSignedPropertiesID . '">' .
      '<xades:SignedSignatureProperties><xades:SigningTime>' .
      date('c', $signTime) . '</xades:SigningTime>' .
      '<xades:SigningCertificate><xades:Cert><xades:CertDigest>' .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod><ds:DigestValue>' . $certDigest .
      '</ds:DigestValue></xades:CertDigest><xades:IssuerSerial>' .
      '<ds:X509IssuerName>' . $certIssuer .
      '</ds:X509IssuerName><ds:X509SerialNumber>' .
      $certData['serialNumber'] . '</ds:X509SerialNumber>' .
      '</xades:IssuerSerial></xades:Cert></xades:SigningCertificate>' .
      '<xades:SignaturePolicyIdentifier><xades:SignaturePolicyId>' .
      '<xades:SigPolicyId><xades:Identifier>' . $this->signPolicy['url'] .
      '</xades:Identifier><xades:Description>' . $this->signPolicy['name'] .
      '</xades:Description></xades:SigPolicyId><xades:SigPolicyHash>' .
      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
      '</ds:DigestMethod><ds:DigestValue>' . $this->signPolicy['digest'] .
      '</ds:DigestValue></xades:SigPolicyHash></xades:SignaturePolicyId>' .
      '</xades:SignaturePolicyIdentifier><xades:SignerRole>' .
      '<xades:ClaimedRoles><xades:ClaimedRole>emisor</xades:ClaimedRole>' .
      '</xades:ClaimedRoles></xades:SignerRole>' .
      '</xades:SignedSignatureProperties><xades:SignedDataObjectProperties>' .
      '<xades:DataObjectFormat ObjectReference="#Reference-ID-' .
      $this->referenceID . '"><xades:Description>Factura electrónica' .
      '</xades:Description><xades:MimeType>text/xml</xades:MimeType>' .
      '</xades:DataObjectFormat></xades:SignedDataObjectProperties>' .
      '</xades:SignedProperties>';

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
    $propDigest = base64_encode(sha1(str_replace('<xades:SignedProperties',
      '<xades:SignedProperties ' . $xmlns, $prop), true));
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
    $sig = '<ds:Signature xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" ' .
      'Id="Signature' . $this->signatureID . '">' . "\n" . $sInfo . "\n" .
      '<ds:SignatureValue Id="SignatureValue' . $this->signatureValueID . '">' .
      "\n" . $signatureResult . '</ds:SignatureValue>' . "\n" . $kInfo . "\n" .
      '<ds:Object Id="Signature' . $this->signatureID . '-Object' .
      $this->signatureObjectID . '"><xades:QualifyingProperties ' .
      'Target="#Signature' . $this->signatureID . '">' . $prop .
      '</xades:QualifyingProperties></ds:Object></ds:Signature>';

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
      $this->padTotal($totals['invoiceAmount']) . '</TotalAmount>' .
      '</TotalInvoicesAmount><TotalOutstandingAmount><TotalAmount>' .
      $this->padTotal($totals['invoiceAmount']) . '</TotalAmount>' .
      '</TotalOutstandingAmount><TotalExecutableAmount>' .
      '<TotalAmount>' . $this->padTotal($totals['invoiceAmount']) .
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
    foreach (["taxesOutputs", "taxesWithheld"] as $i=>$taxesGroup) {
      if (count($totals[$taxesGroup]) == 0) continue;
      $xmlTag = ucfirst($taxesGroup); // Just capitalize variable name
      $xml .= "<$xmlTag>";
      foreach ($totals[$taxesGroup] as $type=>$taxRows) {
        foreach ($taxRows as $rate=>$tax) {
          $xml .= '<Tax><TaxTypeCode>' . $type . '</TaxTypeCode><TaxRate>' .
            $rate . '</TaxRate><TaxableBase><TotalAmount>' .
            $this->padTotal($tax['base']) . '</TotalAmount></TaxableBase>' .
            '<TaxAmount><TotalAmount>' . $this->padTotal($tax['amount']) .
            '</TotalAmount></TaxAmount></Tax>';
        }
      }
      $xml .= "</$xmlTag>";
    }

    // Add invoice totals
    $xml .= '<InvoiceTotals><TotalGrossAmount>' .
      $this->padTotal($totals['grossAmount']) . '</TotalGrossAmount>' .
      '<TotalGeneralDiscounts>0.00</TotalGeneralDiscounts>' .
      '<TotalGeneralSurcharges>0.00</TotalGeneralSurcharges>' .
      '<TotalGrossAmountBeforeTaxes>' .
      $this->padTotal($totals['grossAmountBeforeTaxes']) .
      '</TotalGrossAmountBeforeTaxes><TotalTaxOutputs>' .
      $this->padTotal($totals['totalTaxesOutputs']) .
      '</TotalTaxOutputs><TotalTaxesWithheld>' .
      $this->padTotal($totals['totalTaxesWithheld']) . '</TotalTaxesWithheld>' .
      '<InvoiceTotal>' . $this->padTotal($totals['invoiceAmount']) .
      '</InvoiceTotal><TotalOutstandingAmount>' .
      $this->padTotal($totals['invoiceAmount']) . '</TotalOutstandingAmount>' .
      '<TotalExecutableAmount>' . $this->padTotal($totals['invoiceAmount']) .
      '</TotalExecutableAmount></InvoiceTotals>';

    // Add invoice items
    $xml .= '<Items>';
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData();
      $xml .= '<InvoiceLine><ItemDescription>' . $item['name'] .
        '</ItemDescription><Quantity>' . $this->padTotal($item['quantity']) .
        '</Quantity><UnitOfMeasure>01</UnitOfMeasure><UnitPriceWithoutTax>' .
        $this->padItem($item['unitPriceWithoutTax']) .
        '</UnitPriceWithoutTax><TotalCost>' .
        $this->padTotal($item['totalAmountWithoutTax']) . '</TotalCost>' .
        '<GrossAmount>' . $this->padTotal($item['grossAmount']) .
        '</GrossAmount>';

      // Add item taxes
      // NOTE: As you can see here, taxesWithheld is before taxesOutputs.
      // This is intentional, as most official administrations would mark the
      // invoice as invalid XML if the order is incorrect.
      foreach (["taxesWithheld", "taxesOutputs"] as $taxesGroup) {
        if (count($item[$taxesGroup]) == 0) continue;
        $xmlTag = ucfirst($taxesGroup); // Just capitalize variable name
        $xml .= "<$xmlTag>";
        foreach ($item[$taxesGroup] as $type=>$tax) {
          $xml .= '<Tax><TaxTypeCode>' . $type . '</TaxTypeCode>' .
            '<TaxRate>' . $tax['rate'] . '</TaxRate><TaxableBase>' .
            '<TotalAmount>' . $this->padTotal($item['totalAmountWithoutTax']) .
            '</TotalAmount></TaxableBase><TaxAmount><TotalAmount>' .
            $this->padTotal($tax['amount']) . '</TotalAmount></TaxAmount>' .
            '</Tax>';
        }
        $xml .= "</$xmlTag>";
      }

      // Add item additional information
      if (!is_null($item['description'])) {
        $xml .= '<AdditionalLineItemInformation>' . $item['description'] .
          '</AdditionalLineItemInformation>';
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
        $this->padTotal($totals['invoiceAmount']) . '</InstallmentAmount>' .
        '<PaymentMeans>' . $this->header['paymentMethod'] . '</PaymentMeans>';
      if ($this->header['paymentMethod'] == self::PAYMENT_TRANSFER) {
        $xml .= '<AccountToBeCredited><IBAN>' . $this->header['paymentIBAN'] .
          '</IBAN></AccountToBeCredited>';
      }
      $xml .= '</Installment></PaymentDetails>';
    }

    // Add legal literals
    if (count($this->legalLiterals) > 0) {
      $xml .= '<LegalLiterals>';
      foreach ($this->legalLiterals as $reference) {
        $xml .= '<LegalReference>' . $reference . '</LegalReference>';
      }
      $xml .= '</LegalLiterals>';
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
