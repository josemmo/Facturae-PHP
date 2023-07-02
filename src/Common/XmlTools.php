<?php
namespace josemmo\Facturae\Common;

class XmlTools {

  /**
   * Escape XML value
   * @param  string $value Input value
   * @return string        Escaped input
   */
  public static function escape($value) {
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
  public static function randomId() {
    if (function_exists('random_int')) return random_int(0x10000000, 0x7FFFFFFF);
    return rand(100000, 999999);
  }


  /**
   * Get namespaces from root element
   * @param  string               $xml XML document
   * @return array<string,string>      Namespaces in the form of <name, value>
   */
  public static function getNamespaces($xml) {
    $namespaces = [];

    // Extract element opening tag
    $xml = explode('>', $xml, 2);
    $xml = $xml[0];

    // Extract namespaces
    $matches = [];
    preg_match_all('/\s(xmlns:[0-9a-z]+)=["\'](.+?)["\']/i', $xml, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $namespaces[$match[1]] = $match[2];
    }

    return $namespaces;
  }


  /**
   * Inject namespaces
   * @param  string               $xml        Input XML
   * @param  array<string,string> $namespaces Namespaces to inject in the form of <name, value>
   * @return string                           Canonicalized XML with new namespaces
   */
  public static function injectNamespaces($xml, $namespaces) {
    $xml = explode('>', $xml, 2);

    // Get element name (in the form of "<name")
    $elementName = preg_split('/\s/', $xml[0], 2)[0];

    // Include missing previous namespaces and attributes
    $matches = [];
    preg_match_all('/\s([0-9a-z:]+)=["\'](.+?)["\']/i', $xml[0], $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      if (!isset($namespaces[$match[1]])) {
        $namespaces[$match[1]] = $match[2];
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
  public static function c14n($xml) {
    $xml = str_replace("\r", '', $xml);
    $xml = preg_replace_callback('/<!\[CDATA\[(.*?)\]\]>/', function($match) {
      return self::escape($match[1]);
    }, $xml);
    $xml = preg_replace('/<([0-9a-z:]+?) ?\/>/i', '<$1></$1>', $xml);
    return $xml;
  }


  /**
   * To Base64
   * @param  string  $bytes  Input
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Base64 response
   */
  public static function toBase64($bytes, $pretty=false) {
    $res = base64_encode($bytes);
    return $pretty ? self::prettify($res) : $res;
  }


  /**
   * Prettify
   * @param  string $input Input string
   * @return string        Multi-line resposne
   */
  private static function prettify($input) {
    return chunk_split($input, 76, "\n");
  }


  /**
   * Get digest in SHA-512
   * @param  string  $input  Input string
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Digest
   */
  public static function getDigest($input, $pretty=false) {
    return self::toBase64(hash("sha512", $input, true), $pretty);
  }


  /**
   * Get certificate
   * @param  string  $pem    Certificate for the public key in PEM format
   * @param  boolean $pretty Pretty Base64 response
   * @return string          Base64 Certificate
   */
  public static function getCert($pem, $pretty=true) {
    $pem = str_replace("-----BEGIN CERTIFICATE-----", "", $pem);
    $pem = str_replace("-----END CERTIFICATE-----", "", $pem);
    $pem = str_replace(["\r", "\n"], ['', ''], $pem);
    if ($pretty) $pem = self::prettify($pem);
    return $pem;
  }


  /**
   * Get certificate digest in SHA-512
   * @param  string  $publicKey Public Key
   * @param  boolean $pretty    Pretty Base64 response
   * @return string             Base64 Digest
   */
  public static function getCertDigest($publicKey, $pretty=false) {
    $digest = openssl_x509_fingerprint($publicKey, "sha512", true);
    return self::toBase64($digest, $pretty);
  }


  /**
   * Get signature in SHA-512
   * @param  string  $payload    Data to sign
   * @param  string  $privateKey Private Key
   * @param  boolean $pretty     Pretty Base64 response
   * @return string              Base64 Signature
   */
  public static function getSignature($payload, $privateKey, $pretty=true) {
    openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA512);
    return self::toBase64($signature, $pretty);
  }

}
