<?php
namespace josemmo\Facturae\Common;

class XmlsigTools {

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
   * Get certificate
   * @param  string  $publicKey Public Key
   * @param  boolean $pretty    Pretty Base64 response
   * @return string             Base64 Certificate
   */
  public function getCert($publicKey, $pretty=true) {
    openssl_x509_export($publicKey, $publicPEM);
    $publicPEM = str_replace("-----BEGIN CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("-----END CERTIFICATE-----", "", $publicPEM);
    $publicPEM = str_replace("\n", "", str_replace("\r", "", $publicPEM));
    if ($pretty) $publicPEM = $this->prettify($publicPEM);
    return $publicPEM;
  }


  /**
   * Get certificate digest
   * @param  string  $publicKey Public Key
   * @param  boolean $pretty    Pretty Base64 response
   * @return string             Base64 Digest
   */
  public function getCertDigest($publicKey, $pretty=false) {
    $digest = openssl_x509_fingerprint($publicKey, "sha1", true);
    return $this->toBase64($digest, $pretty);
  }


  /**
   * Get signature
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
