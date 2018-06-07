<?php
namespace josemmo\Facturae\Controller;

/**
 * Implements all properties and methods needed for an instantiable
 * @link{josemmo\Facturae\Facturae} to be signed and time stamped.
 */
abstract class FacturaeSignable extends FacturaeUtils {

  protected $signTime = null;
  protected $signPolicy = null;
  protected $timestampServer = null;
  private $timestampUser = null;
  private $timestampPass = null;
  private $publicKey = null;
  private $privateKey = null;


  /**
   * Load a PKCS#12 Certificate Store
   *
   * @param  string  $pkcs12File The certificate store file name
   * @param  string  $pkcs12Pass Password for unlocking the PKCS#12 file
   * @return boolean             Success
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
   * @param  string  $publicPath  Path to public key PEM file
   * @param  string  $privatePath Path to private key PEM file
   * @param  string  $passphrase  Private key passphrase
   * @return boolean              Success
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
   * Set sign time
   *
   * @param int|string $time Time of the signature
   */
  public function setSignTime($time) {
    $this->signTime = is_string($time) ? strtotime($time) : $time;
  }


  /**
   * Set timestamp server
   *
   * @param string $server Timestamp Authority URL
   * @param string $user   TSA User
   * @param string $pass   TSA Password
   */
  public function setTimestampServer($server, $user=null, $pass=null) {
    $this->timestampServer = $server;
    $this->timestampUser = $user;
    $this->timestampPass = $pass;
  }


  /**
   * Sign
   *
   * @param  string  $publicPath  Path to public key PEM file or PKCS#12
   *                              certificate store
   * @param  string  $privatePath Path to private key PEM file (should be null
   *                              in case of PKCS#12)
   * @param  string  $passphrase  Private key passphrase
   * @param  array   $policy      Facturae sign policy
   * @return boolean              Success
   */
  public function sign($publicPath, $privatePath=null, $passphrase="",
                       $policy=self::SIGN_POLICY_3_1) {
    $this->publicKey = null;
    $this->privateKey = null;
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
   *
   * @param  string $xml Unsigned XML document
   * @return string      Signed XML document
   */
  protected function injectSignature($xml) {
    // Make sure we have all we need to sign the document
    if (empty($this->publicKey) || empty($this->privateKey)) return $xml;

    // Normalize document
    $xml = str_replace("\r", "", $xml);

    // Define namespace
    $xmlns = $this->getNamespaces();

    // Prepare signed properties
    $signTime = is_null($this->signTime) ? time() : $this->signTime;
    $certData = openssl_x509_parse($this->publicKey);
    $certDigest = openssl_x509_fingerprint($this->publicKey, "sha1", true);
    $certDigest = base64_encode($certDigest);
    $certIssuer = array();
    foreach ($certData['issuer'] as $item=>$value) {
      $certIssuer[] = $item . '=' . $value;
    }
    $certIssuer = implode(',', $certIssuer);

    // Generate signed properties
    $prop = '<xades:SignedProperties Id="Signature' . $this->signatureID .
            '-SignedProperties' . $this->signatureSignedPropertiesID . '">' .
              '<xades:SignedSignatureProperties>' .
                '<xades:SigningTime>' . date('c', $signTime) . '</xades:SigningTime>' .
                '<xades:SigningCertificate>' .
                  '<xades:Cert>' .
                    '<xades:CertDigest>' .
                      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
                      '<ds:DigestValue>' . $certDigest . '</ds:DigestValue>' .
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
                      '<xades:Identifier>' . $this->signPolicy['url'] . '</xades:Identifier>' .
                      '<xades:Description>' . $this->signPolicy['name'] . '</xades:Description>' .
                    '</xades:SigPolicyId>' .
                    '<xades:SigPolicyHash>' .
                      '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
                      '<ds:DigestValue>' . $this->signPolicy['digest'] . '</ds:DigestValue>' .
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
                '<xades:DataObjectFormat ObjectReference="#Reference-ID-' . $this->referenceID . '">' .
                  '<xades:Description>Factura electrónica</xades:Description>' .
                  '<xades:MimeType>text/xml</xades:MimeType>' .
                '</xades:DataObjectFormat>' .
              '</xades:SignedDataObjectProperties>' .
            '</xades:SignedProperties>';

    // Prepare key info
    $publicPEM = "";
    openssl_x509_export($this->publicKey, $publicPEM);
    $publicPEM = str_replace("-----BEGIN CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("-----END CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("\n", "", $publicPEM);
    $publicPEM = str_replace("\r", "", chunk_split($publicPEM, 76));

    $privateData = openssl_pkey_get_details($this->privateKey);
    $modulus = chunk_split(base64_encode($privateData['rsa']['n']), 76);
    $modulus = str_replace("\r", "", $modulus);
    $exponent = base64_encode($privateData['rsa']['e']);

    // Generate KeyInfo
    $kInfo = '<ds:KeyInfo Id="Certificate' . $this->certificateID . '">' . "\n" .
               '<ds:X509Data>' . "\n" .
                 '<ds:X509Certificate>' . "\n" . $publicPEM . '</ds:X509Certificate>' . "\n" .
               '</ds:X509Data>' . "\n" .
               '<ds:KeyValue>' . "\n" .
                 '<ds:RSAKeyValue>' . "\n" .
                   '<ds:Modulus>' . "\n" . $modulus . '</ds:Modulus>' . "\n" .
                   '<ds:Exponent>' . $exponent . '</ds:Exponent>' . "\n" .
                 '</ds:RSAKeyValue>' . "\n" .
               '</ds:KeyValue>' . "\n" .
             '</ds:KeyInfo>';

    // Calculate digests
    $propDigest = base64_encode(sha1(str_replace('<xades:SignedProperties',
      '<xades:SignedProperties ' . $xmlns, $prop), true));
    $kInfoDigest = base64_encode(sha1(str_replace('<ds:KeyInfo',
      '<ds:KeyInfo ' . $xmlns, $kInfo), true));
    $documentDigest = base64_encode(sha1($xml, true));

    // Generate SignedInfo
    $sInfo = '<ds:SignedInfo Id="Signature-SignedInfo' . $this->signedInfoID . '">' . "\n" .
               '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
               '</ds:CanonicalizationMethod>' . "\n" .
               '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1">' .
               '</ds:SignatureMethod>' . "\n" .
               '<ds:Reference Id="SignedPropertiesID' . $this->signedPropertiesID . '" ' .
               'Type="http://uri.etsi.org/01903#SignedProperties" ' .
               'URI="#Signature' . $this->signatureID . '-SignedProperties' .
               $this->signatureSignedPropertiesID . '">' . "\n" .
                 '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
                 '</ds:DigestMethod>' . "\n" .
                 '<ds:DigestValue>' . $propDigest . '</ds:DigestValue>' . "\n" .
               '</ds:Reference>' . "\n" .
               '<ds:Reference URI="#Certificate' . $this->certificateID . '">' . "\n" .
                 '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
                 '</ds:DigestMethod>' . "\n" .
                 '<ds:DigestValue>' . $kInfoDigest . '</ds:DigestValue>' . "\n" .
               '</ds:Reference>' . "\n" .
               '<ds:Reference Id="Reference-ID-' . $this->referenceID . '" URI="">' . "\n" .
                 '<ds:Transforms>' . "\n" .
                   '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature">' .
                   '</ds:Transform>' . "\n" .
                 '</ds:Transforms>' . "\n" .
                 '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">' .
                 '</ds:DigestMethod>' . "\n" .
                 '<ds:DigestValue>' . $documentDigest . '</ds:DigestValue>' . "\n" .
               '</ds:Reference>' . "\n" .
             '</ds:SignedInfo>';

    // Calculate signature
    $signaturePayload = str_replace('<ds:SignedInfo', '<ds:SignedInfo ' . $xmlns, $sInfo);
    openssl_sign($signaturePayload, $signatureResult, $this->privateKey);
    $signatureResult = chunk_split(base64_encode($signatureResult), 76);
    $signatureResult = str_replace("\r", "", $signatureResult);

    // Make signature
    $sig = '<ds:Signature xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="Signature' . $this->signatureID . '">' . "\n" .
             $sInfo . "\n" .
             '<ds:SignatureValue Id="SignatureValue' . $this->signatureValueID . '">' . "\n" .
               $signatureResult .
             '</ds:SignatureValue>' . "\n" .
             $kInfo . "\n" .
             '<ds:Object Id="Signature' . $this->signatureID . '-Object' . $this->signatureObjectID . '">' .
               '<xades:QualifyingProperties Target="#Signature' . $this->signatureID . '">' .
                 $prop .
               '</xades:QualifyingProperties>' .
             '</ds:Object>' .
           '</ds:Signature>';

    // Inject signature
    $xml = str_replace('</fe:Facturae>', $sig . '</fe:Facturae>', $xml);

    // Inject timestamp
    if (!empty($this->timestampServer)) $xml = $this->injectTimestamp($xml);

    return $xml;
  }


  /**
   * Inject timestamp
   *
   * @param  string $signedXml Signed XML document
   * @return string            Signed and timestamped XML document
   */
  private function injectTimestamp($signedXml) {
    // Prepare data to timestamp
    $payload = explode('<ds:SignatureValue', $signedXml)[1];
    $payload = explode('</ds:SignatureValue>', $payload)[0];
    $payload = '<ds:SignatureValue ' . $this->getNamespaces() . $payload . '</ds:SignatureValue>';

    // Create TimeStampQuery in ASN1 using SHA-1
    $tsq = "302c0201013021300906052b0e03021a05000414";
    $tsq .= hash('sha1', $payload);
    $tsq .= "0201000101ff";
    $tsq = hex2bin($tsq);

    // Await TimeStampRequest
    $chOpts = array(
      CURLOPT_URL => $this->timestampServer,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_BINARYTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_CONNECTTIMEOUT => 0,
      CURLOPT_TIMEOUT => 10, // 10 seconds timeout
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $tsq,
      CURLOPT_HTTPHEADER => array("Content-Type: application/timestamp-query"),
      CURLOPT_USERAGENT => self::$USER_AGENT
    );
    if (!empty($this->timestampUser) && !empty($this->timestampPass)) {
      $chOpts[CURLOPT_USERPWD] = $this->timestampUser . ":" . $this->timestampPass;
    }
    $ch = curl_init();
    curl_setopt_array($ch, $chOpts);
    $tsr = curl_exec($ch);
    if ($tsr === false) throw new \Exception('cURL error: ' . curl_error($ch));
    curl_close($ch);

    // Validate TimeStampRequest
    $responseCode = substr($tsr, 6, 3);
    if ($responseCode !== "\02\01\00") { // Bytes for INTEGER 0 in ASN1
      throw new \Exception('Invalid TSR response code');
    }

    // Extract TimeStamp from TimeStampRequest and inject into XML document
    $timeStamp = substr($tsr, 9);
    $tsXml = '<xades:UnsignedProperties Id="Signature' . $this->signatureID . '-UnsignedProperties' . $this->random() . '">' .
               '<xades:UnsignedSignatureProperties>' .
                 '<xades:SignatureTimeStamp Id="Timestamp-' . $this->random() . '">' .
                   '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">' .
                   '</ds:CanonicalizationMethod>' .
                   '<xades:EncapsulatedTimeStamp>' . "\n" .
                     str_replace("\r", "", chunk_split(base64_encode($timeStamp), 76)) .
                   '</xades:EncapsulatedTimeStamp>' .
                 '</xades:SignatureTimeStamp>' .
               '</xades:UnsignedSignatureProperties>' .
             '</xades:UnsignedProperties>';
    $signedXml = str_replace('</xades:QualifyingProperties>', $tsXml . '</xades:QualifyingProperties>', $signedXml);
    return $signedXml;
  }

}
