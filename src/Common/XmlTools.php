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
   * Inject namespaces
   * @param  string          $xml   Input XML
   * @param  string|string[] $newNs Namespaces
   * @return string                 Canonicalized XML with new namespaces
   */
  public function injectNamespaces($xml, $newNs) {
    if (!is_array($newNs)) $newNs = array($newNs);
    $xml = explode(">", $xml, 2);
    $oldNs = explode(" ", $xml[0]);
    $elementName = array_shift($oldNs);

    // Combine and sort namespaces
    $xmlns = array();
    $attributes = array();
    foreach (array_merge($oldNs, $newNs) as $name) {
      if (strpos($name, 'xmlns:') === 0) {
        $xmlns[] = $name;
      } else {
        $attributes[] = $name;
      }
    }
    sort($xmlns);
    sort($attributes);
    $ns = array_merge($xmlns, $attributes);

    // Generate new XML element
    $xml = $elementName . " " . implode(' ', $ns) . ">" . $xml[1];
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
   * Get signature in SHA-1
   * @param  string  $payload    Data to sign
   * @param  string  $privateKey Private Key
   * @param  boolean $pretty     Pretty Base64 response
   * @return string              Base64 Signature
   */
  public function getSignature($payload, $privateKey, $pretty=true) {
    openssl_sign($payload, $signature, $privateKey);
    return $this->toBase64($signature, $pretty);
  }

}
