<?php
namespace josemmo\Facturae\Common;

class XmlTools {

  /**
   * Escape XML value
   * @param  string $value Input value
   * @return string        Escaped input
   */
  public function escape($value) {
    return htmlspecialchars($value, ENT_XML1, 'UTF-8');
  }


  /**
   * Generate random ID
   *
   * This method is used for generating random IDs required when signing the
   * document.
   *
   * @return int Random number
   */
  public function randomId() {
    if (function_exists('random_int')) return random_int(0x10000000, 0x7FFFFFFF);
    return rand(100000, 999999);
  }


  /**
   * Get namespaces from root element
   * @param  string               $xml XML document
   * @return array<string,string>      Namespaces in the form of <name, value>
   */
  public function getNamespaces($xml) {
    $namespaces = [];

    $xml = explode('>', $xml, 2);
    $rawNamespaces = explode(' ', preg_replace('/\s+/', ' ', $xml[0]));
    array_shift($rawNamespaces);
    foreach ($rawNamespaces as $part) {
      list($name, $value) = explode('=', $part, 2);
      if (mb_strpos($name, 'xmlns:') === 0) {
        $namespaces[$name] = mb_substr($value, 1, -1);
      }
    }

    return $namespaces;
  }


  /**
   * Inject namespaces
   * @param  string               $xml        Input XML
   * @param  array<string,string> $namespaces Namespaces to inject in the form of <name, value>
   * @return string                           Canonicalized XML with new namespaces
   */
  public function injectNamespaces($xml, $namespaces) {
    $xml = explode('>', $xml, 2);
    $rawNamespaces = explode(' ', preg_replace('/\s+/', ' ', $xml[0]));
    $elementName = array_shift($rawNamespaces);

    // Include missing previous namespaces
    foreach ($rawNamespaces as $part) {
      list($name, $value) = explode('=', $part, 2);
      if (!isset($namespaces[$name])) {
        $namespaces[$name] = mb_substr($value, 1, -1);
      }
    }
    ksort($namespaces);

    // Prepare new XML element parts
    $xmlns = [];
    $attributes = [];
    foreach ($namespaces as $name=>$value) {
      if (mb_strpos($name, 'xmlns:') === 0) {
        $xmlns[] = "$name=\"$value\"";
      } else {
        $attributes[] = "$name=\"$value\"";
      }
    }

    // Generate new XML element
    $xml = $elementName . " " . implode(' ', array_merge($xmlns, $attributes)) . ">" . $xml[1];
    return $xml;
  }


  /**
   * Canonicalize XML document
   * @param  string $xml Input XML
   * @return string      Canonicalized XML
   */
  public function c14n($xml) {
    $xml = str_replace("\r", '', $xml);
    // TODO: add missing transformations
    return $xml;
  }


  /**
   * To Base64
   * @param  string  $bytes  Input
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Base64 response
   */
  public function toBase64($bytes, $pretty=false) {
    $res = base64_encode($bytes);
    return $pretty ? $this->prettify($res) : $res;
  }


  /**
   * Prettify
   * @param  string $input Input string
   * @return string        Multi-line resposne
   */
  private function prettify($input) {
    return chunk_split($input, 76, "\n");
  }


  /**
   * Get digest in SHA-512
   * @param  string  $input  Input string
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Digest
   */
  public function getDigest($input, $pretty=false) {
    return $this->toBase64(hash("sha512", $input, true), $pretty);
  }


  /**
   * Get certificate
   * @param  string  $pem    Certificate for the public key in PEM format
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Base64 Certificate
   */
  public function getCert($pem, $pretty=true) {
    $pem = str_replace("-----BEGIN CERTIFICATE-----", "", $pem);
    $pem = str_replace("-----END CERTIFICATE-----", "", $pem);
    $pem = str_replace("\n", "", str_replace("\r", "", $pem));
    if ($pretty) $pem = $this->prettify($pem);
    return $pem;
  }


  /**
   * Get certificate digest in SHA-512
   * @param  string  $publicKey Public Key
   * @param  boolean $pretty    Pretty Base64 response
   * @return string             Base64 Digest
   */
  public function getCertDigest($publicKey, $pretty=false) {
    $digest = openssl_x509_fingerprint($publicKey, "sha512", true);
    return $this->toBase64($digest, $pretty);
  }


  /**
   * Get signature in SHA-512
   * @param  string  $payload    Data to sign
   * @param  string  $privateKey Private Key
   * @param  boolean $pretty     Pretty Base64 response
   * @return string              Base64 Signature
   */
  public function getSignature($payload, $privateKey, $pretty=true) {
    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
    return $this->toBase64($signature, $pretty);
  }

}
