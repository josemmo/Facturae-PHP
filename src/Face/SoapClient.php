<?php
namespace josemmo\Facturae\Face;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\Common\KeyPairReaderTrait;
use josemmo\Facturae\Common\XmlTools;

abstract class SoapClient {

  const REQUEST_EXPIRATION = 60; // In seconds

  use KeyPairReaderTrait;

  /**
   * SoapClient constructor
   *
   * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string      $storeOrCertificate Certificate or PKCS#12 store
   * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|string|null $privateKey         Private key (`null` for PKCS#12)
   * @param string                                                         $passphrase         Store or private key passphrase
   */
  public function __construct($storeOrCertificate, $privateKey=null, $passphrase='') {
    if ($privateKey === null) {
      $this->loadPkcs12($storeOrCertificate, $passphrase);
    } else {
      $this->addCertificate($storeOrCertificate);
      $this->setPrivateKey($privateKey, $passphrase);
    }
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
   * Send SOAP request
   * @param  string            $body Request body
   * @return \SimpleXMLElement       Response
   */
  protected function request($body) {
    // Generate random IDs for this request
    $bodyId = "BodyId-" . XmlTools::randomId();
    $certId = "CertId-" . XmlTools::randomId();
    $keyId = "KeyId-" . XmlTools::randomId();
    $strId = "SecTokId-" . XmlTools::randomId();
    $timestampId = "TimestampId-" . XmlTools::randomId();
    $sigId = "SignatureId-" . XmlTools::randomId();

    // Define namespaces
    $ns = [
      'xmlns:soapenv' => 'http://schemas.xmlsoap.org/soap/envelope/',
      'xmlns:web'     => $this->getWebNamespace(),
      'xmlns:ds'      => 'http://www.w3.org/2000/09/xmldsig#',
      'xmlns:wsu'     => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd',
      'xmlns:wsse'    => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'
    ];

    // Generate request body
    $reqBody = '<soapenv:Body wsu:Id="' . $bodyId . '">' . $body . '</soapenv:Body>';
    $bodyDigest = XmlTools::getDigest(XmlTools::injectNamespaces($reqBody, $ns));

    // Generate timestamp
    $timeCreated = time();
    $timeExpires = $timeCreated + self::REQUEST_EXPIRATION;
    $reqTimestamp = '<wsu:Timestamp wsu:Id="' . $timestampId . '">' .
        '<wsu:Created>' . date('c', $timeCreated) . '</wsu:Created>' .
        '<wsu:Expires>' . date('c', $timeExpires) . '</wsu:Expires>' .
      '</wsu:Timestamp>';
    $timestampDigest = XmlTools::getDigest(
      XmlTools::injectNamespaces($reqTimestamp, $ns)
    );

    // Generate request header
    $reqHeader = '<soapenv:Header>';
    $reqHeader .= '<wsse:Security soapenv:mustUnderstand="1">';
    $reqHeader .= '<wsse:BinarySecurityToken ' .
      'EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ' .
      'wsu:Id="' . $certId . '" ' .
      'ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3">' .
        XmlTools::getCert($this->publicChain[0], false) .
      '</wsse:BinarySecurityToken>';

    // Generate signed info
    $signedInfo = '<ds:SignedInfo>' .
        '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
        '</ds:CanonicalizationMethod>' .
        '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512"></ds:SignatureMethod>' .
        '<ds:Reference URI="#' . $timestampId . '">' .
          '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"></ds:DigestMethod>' .
          '<ds:DigestValue>' . $timestampDigest . '</ds:DigestValue>' .
        '</ds:Reference>' .
        '<ds:Reference URI="#' . $bodyId . '">' .
          '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"></ds:DigestMethod>' .
          '<ds:DigestValue>' . $bodyDigest . '</ds:DigestValue>' .
        '</ds:Reference>' .
      '</ds:SignedInfo>';
    $signedInfoPayload = XmlTools::injectNamespaces($signedInfo, $ns);

    // Add signature and KeyInfo to header
    $reqHeader .= '<ds:Signature Id="' . $sigId . '">' .
      $signedInfo .
      '<ds:SignatureValue>' .
        XmlTools::getSignature($signedInfoPayload, $this->privateKey, false) .
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
    $req = XmlTools::injectNamespaces($req, $ns);
    $req = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $req;

    // Extract SOAP action from "<web:ACTION></web:ACTION>"
    $soapAction = substr($body, 5, strpos($body, '>')-5);
    $soapAction = $this->getWebNamespace() . "#$soapAction";

    // Send request
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_URL => $this->getEndpointUrl(),
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $req,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: text/xml",
        "SOAPAction: $soapAction"
      ),
      CURLOPT_USERAGENT => Facturae::USER_AGENT
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    unset($ch);

    // Parse response
    $xml = new \DOMDocument();
    $xml->loadXML($res);
    $xml = $xml->getElementsByTagName('Body')->item(0)
      ->getElementsByTagName('*')->item(0);
    while (true) {
      $child = $xml->getElementsByTagName('return')->item(0);
      if (is_null($child)) break;
      $xml = $child;
    }
    $xml = simplexml_import_dom($xml);

    return $xml;
  }

}
