<?php
namespace josemmo\Facturae\Common;

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

  /**
   * Class constuctor
   */
  public function __construct() {
    $tools = new XmlTools();
    $this->signatureId = 'Signature' . $tools->randomId();
    $this->signedInfoId = 'Signature-SignedInfo' . $tools->randomId();
    $this->signedPropertiesId = 'SignedPropertiesID' . $tools->randomId();
    $this->signatureValueId = 'SignatureValue' . $tools->randomId();
    $this->certificateId = 'Certificate' . $tools->randomId();
    $this->referenceId = 'Reference-ID-' . $tools->randomId();
    $this->signatureSignedPropertiesId = $this->signatureId . '-SignedProperties' . $tools->randomId();
    $this->signatureObjectId = $this->signatureId . '-Object' . $tools->randomId();
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
   * Sign XML document
   * @param  string $xml Unsigned XML document
   * @return string      Signed XML document
   * @throws RuntimeException if failed to sign document
   */
  public function sign($xml) {
    $tools = new XmlTools();

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
    $xmlRoot = $tools->injectNamespaces($xmlRoot, [
      'xmlns:ds' => self::XMLNS_DS
    ]);

    // Build list of all namespaces for C14N
    $xmlns = $tools->getNamespaces($xmlRoot);
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
              '<ds:DigestValue>' . $tools->getCertDigest($publicChain[0]) . '</ds:DigestValue>' .
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
      $dsKeyInfo .= '<ds:X509Certificate>' . "\n" . $tools->getCert($pemCertificate) . '</ds:X509Certificate>' . "\n";
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
          $tools->getDigest($tools->injectNamespaces($xadesSignedProperties, $xmlns)) .
        '</ds:DigestValue>' . "\n" .
      '</ds:Reference>' . "\n" .
      '<ds:Reference URI="#' . $this->certificateId . '">' . "\n" .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512">' .
        '</ds:DigestMethod>' . "\n" .
        '<ds:DigestValue>' .
          $tools->getDigest($tools->injectNamespaces($dsKeyInfo, $xmlns)) .
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
        '<ds:DigestValue>' . $tools->getDigest($tools->c14n($xmlRoot)) . '</ds:DigestValue>' . "\n" .
      '</ds:Reference>' . "\n" .
    '</ds:SignedInfo>';

    // Build <ds:Signature /> element
    $dsSignature = '<ds:Signature xmlns:xades="' . self::XMLNS_XADES . '" Id="' . $this->signatureId . '">' . "\n" .
      $dsSignedInfo . "\n" .
      '<ds:SignatureValue Id="' . $this->signatureValueId . '">' . "\n" .
        $tools->getSignature($tools->injectNamespaces($dsSignedInfo, $xmlns), $privateKey) .
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
    // TODO: not implemented
    return $xml;
  }
}
