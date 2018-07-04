<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\Common\KeyPairReader;
use josemmo\Facturae\Common\XmlTools;

abstract class SoapClient {

  const REQUEST_EXPIRATION = 60; // In seconds

  private $publicKey;
  private $privateKey;
  protected $production = true;


  /**
   * SoapClient constructor
   *
   * @param string $publicPath  Path to public key in PEM or PKCS#12 file
   * @param string $privatePath Path to private key (null for PKCS#12)
   * @param string $passphrase  Private key passphrase
   */
  public function __construct($publicPath, $privatePath=null, $passphrase="") {
    $reader = new KeyPairReader($publicPath, $privatePath, $passphrase);
    $this->publicKey = $reader->getPublicKey();
    $this->privateKey = $reader->getPrivateKey();
    unset($reader);
  }


  /**
   * Get endpoint URL
   * @return string Endpoint URL
   */
  protected abstract function getEndpointUrl();


  /**
   * Get web namespace
   * @return string Web namespace
   */
  protected abstract function getWebNamespace();


  /**
   * Set production environment
   * @param boolean $production Is production
   */
  public function setProduction($production) {
    $this->production = $production;
  }


  /**
   * Is production
   * @return boolean Is production
   */
  public function isProduction() {
    return $this->production;
  }


  /**
   * Send SOAP request
   * @param  string           $body Request body
   * @return SimpleXMLElement       Response
   */
  protected function request($body) {
    $tools = new XmlTools();

    // Generate random IDs for this request
    $bodyId = "BodyId-" . $tools->randomId();
    $certId = "CertId-" . $tools->randomId();
    $keyId = "KeyId-" . $tools->randomId();
    $strId = "SecTokId-" . $tools->randomId();
    $timestampId = "TimestampId-" . $tools->randomId();
    $sigId = "SignatureId-" . $tools->randomId();

    // Define namespaces array
    $ns = array(
      "soapenv" => 'xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"',
      "web" => 'xmlns:web="' . $this->getWebNamespace() . '"',
      "ds" => 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
      "wsu" => 'xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"',
      "wsse" => 'xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"'
    );

    // Generate request body
    $reqBody = '<soapenv:Body wsu:Id="' . $bodyId . '">' . $body . '</soapenv:Body>';
    $bodyDigest = $tools->getDigest($tools->injectNamespaces($reqBody, $ns));

    // Generate timestamp
    $timeCreated = time();
    $timeExpires = $timeCreated + self::REQUEST_EXPIRATION;
    $reqTimestamp = '<wsu:Timestamp wsu:Id="' . $timestampId . '">' .
        '<wsu:Created>' . date('c', $timeCreated) . '</wsu:Created>' .
        '<wsu:Expires>' . date('c', $timeExpires) . '</wsu:Expires>' .
      '</wsu:Timestamp>';
    $timestampDigest = $tools->getDigest(
      $tools->injectNamespaces($reqTimestamp, $ns)
    );

    // Generate request header
    $reqHeader = '<soapenv:Header>';
    $reqHeader .= '<wsse:Security soapenv:mustUnderstand="1">';
    $reqHeader .= '<wsse:BinarySecurityToken ' .
      'EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ' .
      'wsu:Id="' . $certId . '" ' .
      'ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3">' .
        $tools->getCert($this->publicKey, false) .
      '</wsse:BinarySecurityToken>';

    // Generate signed info
    $signedInfo = '<ds:SignedInfo>' .
        '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
        '</ds:CanonicalizationMethod>' .
        '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></ds:SignatureMethod>' .
        '<ds:Reference URI="#' . $timestampId . '">' .
          '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
          '<ds:DigestValue>' . $timestampDigest . '</ds:DigestValue>' .
        '</ds:Reference>' .
        '<ds:Reference URI="#' . $bodyId . '">' .
          '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
          '<ds:DigestValue>' . $bodyDigest . '</ds:DigestValue>' .
        '</ds:Reference>' .
      '</ds:SignedInfo>';
    $signedInfoPayload = $tools->injectNamespaces($signedInfo, $ns);

    // Add signature and KeyInfo to header
    $reqHeader .= '<ds:Signature Id="' . $sigId . '">' .
      $signedInfo .
      '<ds:SignatureValue>' .
        $tools->getSignature($signedInfoPayload, $this->privateKey, false) .
      '</ds:SignatureValue>';
    $reqHeader .= '<ds:KeyInfo Id="' . $keyId . '">' .
      '<wsse:SecurityTokenReference wsu:Id="' . $strId . '">' .
        '<wsse:Reference URI="#' . $certId . '" ' .
        'ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3">' .
        '</wsse:Reference>' .
      '</wsse:SecurityTokenReference>' .
      '</ds:KeyInfo>';
    $reqHeader .= '</ds:Signature>';

    // Add timestamp and close header
    $reqHeader .= $reqTimestamp;
    $reqHeader .= '</wsse:Security>';
    $reqHeader .= '</soapenv:Header>';

    // Generate final request
    $req = '<soapenv:Envelope>' . $reqHeader . $reqBody . '</soapenv:Envelope>';
    $req = $tools->injectNamespaces($req, $ns);
    $req = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $req;

    // Send request
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_URL => $this->getEndpointUrl(),
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $req,
      CURLOPT_HTTPHEADER => array("Content-Type: text/xml"),
      CURLOPT_USERAGENT => Facturae::USER_AGENT
    ));
    $res = curl_exec($ch);
    curl_close($ch);

    // Parse response
    $xml = new \DOMDocument();
    $xml->loadXML($res);
    $xml = $xml->getElementsByTagName('Body')->item(0)
      ->getElementsByTagName('*')->item(0)
      ->getElementsByTagName('return')->item(0);
    $xml = simplexml_import_dom($xml);

    return $xml;
  }

}
