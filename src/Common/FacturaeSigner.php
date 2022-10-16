<?php
namespace josemmo\Facturae\Common;

use josemmo\Facturae\Facturae;
use RuntimeException;

/**
 * Class for signing FacturaE XML documents.
 */
final class FacturaeSigner {
  const XMLNS_DS = 'http://www.w3.org/2000/09/xmldsig#';
  const XMLNS_XADES = 'http://uri.etsi.org/01903/v1.3.2#';
  const SIGN_POLICY_NAME = 'Política de Firma FacturaE v3.1';
  const SIGN_POLICY_URL = 'http://www.facturae.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf';
  const SIGN_POLICY_DIGEST = 'Ohixl6upD6av8N7pEvDABhEL6hM=';

  /** @var KeyPairReader|null */
  private $keypairReader = null;
  /** @var int|null */
  private $signingTime = null;
  /** @var string|null */
  private $tsaEndpoint = null;
  /** @var string|null */
  private $tsaUsername = null;
  /** @var string|null */
  private $tsaPassword = null;

  /** @var string */
  public $signatureId;
  /** @var string */
  public $signedInfoId;
  /** @var string */
  public $signedPropertiesId;
  /** @var string */
  public $signatureValueId;
  /** @var string */
  public $certificateId;
  /** @var string */
  public $referenceId;
  /** @var string */
  public $signatureSignedPropertiesId;
  /** @var string */
  public $signatureObjectId;
  /** @var string */
  public $timestampId;

  /**
   * Class constuctor
   */
  public function __construct() {
    $this->regenerateIds();
  }


  /**
   * Regenerate random element IDs
   * @return self This instance
   */
  public function regenerateIds() {
    $this->signatureId = 'Signature' . XmlTools::randomId();
    $this->signedInfoId = 'Signature-SignedInfo' . XmlTools::randomId();
    $this->signedPropertiesId = 'SignedPropertiesID' . XmlTools::randomId();
    $this->signatureValueId = 'SignatureValue' . XmlTools::randomId();
    $this->certificateId = 'Certificate' . XmlTools::randomId();
    $this->referenceId = 'Reference-ID-' . XmlTools::randomId();
    $this->signatureSignedPropertiesId = $this->signatureId . '-SignedProperties' . XmlTools::randomId();
    $this->signatureObjectId = $this->signatureId . '-Object' . XmlTools::randomId();
    $this->timestampId = 'Timestamp-' . XmlTools::randomId();
    return $this;
  }


  /**
   * Set signing time
   * @param  int|string $time Time of the signature as UNIX timestamp or parseable date
   * @return self             This instance
   */
  public function setSigningTime($time) {
    $this->signingTime = is_string($time) ? strtotime($time) : $time;
    return $this;
  }


  /**
   * Set signing key material
   * @param  string      $publicPath  Path to public key PEM file or PKCS#12 certificate store
   * @param  string|null $privatePath Path to private key PEM file (should be null in case of PKCS#12)
   * @param  string      $passphrase  Private key passphrase
   * @return self                     This instance
   */
  public function setSigningKey($publicPath, $privatePath=null, $passphrase='') {
    $this->keypairReader = new KeyPairReader($publicPath, $privatePath, $passphrase);
    return $this;
  }


  /**
   * Can sign
   * @return boolean Whether instance is ready to sign XML documents
   */
  public function canSign() {
    return ($this->keypairReader !== null) &&
      !empty($this->keypairReader->getPublicChain()) &&
      !empty($this->keypairReader->getPrivateKey());
  }


  /**
   * Set timestamp server
   * @param  string      $server Timestamp Authority URL
   * @param  string|null $user   TSA User
   * @param  string|null $pass   TSA Password
   * @return self                This instance
   */
  public function setTimestampServer($server, $user=null, $pass=null) {
    $this->tsaEndpoint = $server;
    $this->tsaUsername = $user;
    $this->tsaPassword = $pass;
    return $this;
  }


  /**
   * Can timestamp
   * @return boolean Whether instance is ready to timestamp signed XML documents
   */
  public function canTimestamp() {
    return ($this->tsaEndpoint !== null);
  }


  /**
   * Sign XML document
   * @param  string $xml Unsigned XML document
   * @return string      Signed XML document
   * @throws RuntimeException if failed to sign document
   */
  public function sign($xml) {
    // Validate signing key material
    if ($this->keypairReader === null) {
      throw new RuntimeException('Missing signing key material');
    }
    $publicChain = $this->keypairReader->getPublicChain();
    if (empty($publicChain)) {
      throw new RuntimeException('Invalid signing key material: chain of certificates is empty');
    }
    $privateKey = $this->keypairReader->getPrivateKey();
    if (empty($privateKey)) {
      throw new RuntimeException('Invalid signing key material: failed to read private key');
    }

    // Extract root element
    $openTagPosition = mb_strpos($xml, '<fe:Facturae ');
    if ($openTagPosition === false) {
      throw new RuntimeException('XML document is missing <fe:Facturae /> element');
    }
    $closeTagPosition = mb_strpos($xml, '</fe:Facturae>');
    if ($closeTagPosition === false) {
      throw new RuntimeException('XML document is missing </fe:Facturae> closing tag');
    }
    $closeTagPosition += 14;
    $xmlRoot = mb_substr($xml, $openTagPosition, $closeTagPosition-$openTagPosition);

    // Inject XMLDSig namespace
    $xmlRoot = XmlTools::injectNamespaces($xmlRoot, [
      'xmlns:ds' => self::XMLNS_DS
    ]);

    // Build list of all namespaces for C14N
    $xmlns = XmlTools::getNamespaces($xmlRoot);
    $xmlns['xmlns:xades'] = self::XMLNS_XADES;

    // Build <xades:SignedProperties /> element
    $signingTime = ($this->signingTime === null) ? time() : $this->signingTime;
    $certData = openssl_x509_parse($publicChain[0]);
    $certIssuer = [];
    foreach ($certData['issuer'] as $item=>$value) {
      $certIssuer[] = "$item=$value";
    }
    $certIssuer = implode(',', array_reverse($certIssuer));
    $xadesSignedProperties = '<xades:SignedProperties Id="'. $this->signatureSignedPropertiesId . '">' .
      '<xades:SignedSignatureProperties>' .
        '<xades:SigningTime>' . date('c', $signingTime) . '</xades:SigningTime>' .
        '<xades:SigningCertificate>' .
          '<xades:Cert>' .
            '<xades:CertDigest>' .
              '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"></ds:DigestMethod>' .
              '<ds:DigestValue>' . XmlTools::getCertDigest($publicChain[0]) . '</ds:DigestValue>' .
            '</xades:CertDigest>' .
            '<xades:IssuerSerial>' .
              '<ds:X509IssuerName>' . $certIssuer . '</ds:X509IssuerName>' .
              '<ds:X509SerialNumber>' . $certData['serialNumber'] . '</ds:X509SerialNumber>' .
            '</xades:IssuerSerial>' .
          '</xades:Cert>' .
        '</xades:SigningCertificate>' .
        '<xades:SignaturePolicyIdentifier>' .
          '<xades:SignaturePolicyId>' .
            '<xades:SigPolicyId>' .
              '<xades:Identifier>' . self::SIGN_POLICY_URL . '</xades:Identifier>' .
              '<xades:Description>' . self::SIGN_POLICY_NAME . '</xades:Description>' .
            '</xades:SigPolicyId>' .
            '<xades:SigPolicyHash>' .
              '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
              '<ds:DigestValue>' . self::SIGN_POLICY_DIGEST . '</ds:DigestValue>' .
            '</xades:SigPolicyHash>' .
          '</xades:SignaturePolicyId>' .
        '</xades:SignaturePolicyIdentifier>' .
        '<xades:SignerRole>' .
          '<xades:ClaimedRoles>' .
            '<xades:ClaimedRole>emisor</xades:ClaimedRole>' .
          '</xades:ClaimedRoles>' .
        '</xades:SignerRole>' .
      '</xades:SignedSignatureProperties>' .
      '<xades:SignedDataObjectProperties>' .
        '<xades:DataObjectFormat ObjectReference="#' . $this->referenceId . '">' .
          '<xades:Description>Factura electrónica</xades:Description>' .
          '<xades:ObjectIdentifier>' .
            '<xades:Identifier Qualifier="OIDAsURN">urn:oid:1.2.840.10003.5.109.10</xades:Identifier>' .
          '</xades:ObjectIdentifier>' .
          '<xades:MimeType>text/xml</xades:MimeType>' .
        '</xades:DataObjectFormat>' .
      '</xades:SignedDataObjectProperties>' .
    '</xades:SignedProperties>';

    // Build <ds:KeyInfo /> element
    $privateData = openssl_pkey_get_details($privateKey);
    $modulus = chunk_split(base64_encode($privateData['rsa']['n']), 76);
    $modulus = str_replace("\r", '', $modulus);
    $exponent = base64_encode($privateData['rsa']['e']);
    $dsKeyInfo = '<ds:KeyInfo Id="' . $this->certificateId . '">' . "\n" . '<ds:X509Data>' . "\n";
    foreach ($publicChain as $pemCertificate) {
      $dsKeyInfo .= '<ds:X509Certificate>' . "\n" . XmlTools::getCert($pemCertificate) . '</ds:X509Certificate>' . "\n";
    }
    $dsKeyInfo .= '</ds:X509Data>' . "\n" .
      '<ds:KeyValue>' . "\n" .
          '<ds:RSAKeyValue>' . "\n" .
            '<ds:Modulus>' . "\n" . $modulus . '</ds:Modulus>' . "\n" .
            '<ds:Exponent>' . $exponent . '</ds:Exponent>' . "\n" .
          '</ds:RSAKeyValue>' . "\n" .
        '</ds:KeyValue>' . "\n" .
      '</ds:KeyInfo>';

    // Build <ds:SignedInfo /> element
    $dsSignedInfo = '<ds:SignedInfo Id="' . $this->signedInfoId . '">' . "\n" .
      '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
      '</ds:CanonicalizationMethod>' . "\n" .
      '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512">' .
      '</ds:SignatureMethod>' . "\n" .
      '<ds:Reference Id="' . $this->signedPropertiesId . '" ' .
      'Type="http://uri.etsi.org/01903#SignedProperties" ' .
      'URI="#' . $this->signatureSignedPropertiesId . '">' . "\n" .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512">' .
        '</ds:DigestMethod>' . "\n" .
        '<ds:DigestValue>' .
          XmlTools::getDigest(XmlTools::injectNamespaces($xadesSignedProperties, $xmlns)) .
        '</ds:DigestValue>' . "\n" .
      '</ds:Reference>' . "\n" .
      '<ds:Reference URI="#' . $this->certificateId . '">' . "\n" .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512">' .
        '</ds:DigestMethod>' . "\n" .
        '<ds:DigestValue>' .
          XmlTools::getDigest(XmlTools::injectNamespaces($dsKeyInfo, $xmlns)) .
        '</ds:DigestValue>' . "\n" .
      '</ds:Reference>' . "\n" .
      '<ds:Reference Id="' . $this->referenceId . '" ' .
      'Type="http://www.w3.org/2000/09/xmldsig#Object" URI="">' . "\n" .
        '<ds:Transforms>' . "\n" .
          '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature">' .
          '</ds:Transform>' . "\n" .
        '</ds:Transforms>' . "\n" .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512">' .
        '</ds:DigestMethod>' . "\n" .
        '<ds:DigestValue>' . XmlTools::getDigest(XmlTools::c14n($xmlRoot)) . '</ds:DigestValue>' . "\n" .
      '</ds:Reference>' . "\n" .
    '</ds:SignedInfo>';

    // Build <ds:Signature /> element
    $dsSignature = '<ds:Signature xmlns:xades="' . self::XMLNS_XADES . '" Id="' . $this->signatureId . '">' . "\n" .
      $dsSignedInfo . "\n" .
      '<ds:SignatureValue Id="' . $this->signatureValueId . '">' . "\n" .
        XmlTools::getSignature(XmlTools::injectNamespaces($dsSignedInfo, $xmlns), $privateKey) .
      '</ds:SignatureValue>' . "\n" .
      $dsKeyInfo . "\n" .
      '<ds:Object Id="' . $this->signatureObjectId . '">' .
        '<xades:QualifyingProperties Target="#' . $this->signatureId . '">' .
          $xadesSignedProperties .
        '</xades:QualifyingProperties>' .
      '</ds:Object>' .
    '</ds:Signature>';

    // Build new document
    $xmlRoot = str_replace('</fe:Facturae>', "$dsSignature</fe:Facturae>", $xmlRoot);
    $xml = mb_substr($xml, 0, $openTagPosition) . $xmlRoot . mb_substr($xml, $closeTagPosition);

    return $xml;
  }


  /**
   * Timestamp XML document
   * @param  string $xml Signed XML document
   * @return string      Signed and timestamped XML document
   * @throws RuntimeException if failed to timestamp document
   */
  public function timestamp($xml) {
    // Validate TSA endpoint
    if ($this->tsaEndpoint === null) {
      throw new RuntimeException('Missing Timestamp Authority URL');
    }

    // Extract root element
    $rootOpenTagPosition = mb_strpos($xml, '<fe:Facturae ');
    if ($rootOpenTagPosition === false) {
      throw new RuntimeException('Signed XML document is missing <fe:Facturae /> element');
    }
    $rootCloseTagPosition = mb_strpos($xml, '</fe:Facturae>');
    if ($rootCloseTagPosition === false) {
      throw new RuntimeException('Signed XML document is missing </fe:Facturae> closing tag');
    }
    $rootCloseTagPosition += 14;
    $xmlRoot = mb_substr($xml, $rootOpenTagPosition, $rootCloseTagPosition-$rootOpenTagPosition);

    // Verify <xades:QualifyingProperties /> element is present
    if (mb_strpos($xmlRoot, '</xades:QualifyingProperties>') === false) {
      throw new RuntimeException('Signed XML document is missing <xades:QualifyingProperties /> element');
    }

    // Extract <ds:SignatureValue /> element
    $signatureOpenTagPosition = mb_strpos($xmlRoot, '<ds:SignatureValue ');
    if ($signatureOpenTagPosition === false) {
      throw new RuntimeException('Signed XML document is missing <ds:SignatureValue /> element');
    }
    $signatureCloseTagPosition = mb_strpos($xmlRoot, '</ds:SignatureValue>');
    if ($signatureCloseTagPosition === false) {
      throw new RuntimeException('Signed XML document is missing </ds:SignatureValue> closing tag');
    }
    $signatureCloseTagPosition += 20;
    $dsSignatureValue = mb_substr($xmlRoot, $signatureOpenTagPosition, $signatureCloseTagPosition-$signatureOpenTagPosition);

    // Canonicalize <ds:SignatureValue /> element
    $xmlns = XmlTools::getNamespaces($xmlRoot);
    $xmlns['xmlns:xades'] = self::XMLNS_XADES;
    $dsSignatureValue = XmlTools::injectNamespaces($dsSignatureValue, $xmlns);

    // Build TimeStampQuery in ASN1 using SHA-512
    $tsq  = "\x30\x59\x02\x01\x01\x30\x51\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x03\x05\x00\x04\x40";
    $tsq .= hash('sha512', $dsSignatureValue, true);
    $tsq .= "\x01\x01\xff";

    // Send query to TSA endpoint
    $chOpts = [
      CURLOPT_URL => $this->tsaEndpoint,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_CONNECTTIMEOUT => 0,
      CURLOPT_TIMEOUT => 10, // 10 seconds timeout
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $tsq,
      CURLOPT_HTTPHEADER => ['Content-Type: application/timestamp-query'],
      CURLOPT_USERAGENT => Facturae::USER_AGENT
    ];
    if ($this->tsaUsername !== null && $this->tsaPassword !== null) {
      $chOpts[CURLOPT_USERPWD] = $this->tsaUsername . ':' . $this->tsaPassword;
    }
    $ch = curl_init();
    curl_setopt_array($ch, $chOpts);
    $tsr = curl_exec($ch);
    if ($tsr === false) {
      throw new RuntimeException('Failed to get TSR from server: ' . curl_error($ch));
    }
    curl_close($ch);
    unset($ch);

    // Validate TimeStampReply
    $responseCode = substr($tsr, 6, 3);
    if ($responseCode !== "\x02\x01\x00") { // Bytes for INTEGER 0 in ASN1
      throw new RuntimeException('Invalid TSR response code: 0x' . bin2hex($responseCode));
    }

    // Build new <xades:UnsignedProperties /> element
    $timestamp = XmlTools::toBase64(substr($tsr, 9), true);
    $xadesUnsignedProperties = '<xades:UnsignedProperties>' .
      '<xades:UnsignedSignatureProperties>' .
        '<xades:SignatureTimeStamp Id="' . $this->timestampId . '">' .
          '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
          '</ds:CanonicalizationMethod>' .
          '<xades:EncapsulatedTimeStamp>' . "\n" . $timestamp . '</xades:EncapsulatedTimeStamp>' .
        '</xades:SignatureTimeStamp>' .
      '</xades:UnsignedSignatureProperties>' .
    '</xades:UnsignedProperties>';

    // Build new document
    $xmlRoot = str_replace('</xades:QualifyingProperties>', "$xadesUnsignedProperties</xades:QualifyingProperties>", $xmlRoot);
    $xml = mb_substr($xml, 0, $rootOpenTagPosition) . $xmlRoot . mb_substr($xml, $rootCloseTagPosition);

    return $xml;
  }
}
