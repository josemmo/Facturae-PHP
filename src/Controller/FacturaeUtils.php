<?php
namespace josemmo\Facturae\Controller;

/**
 * Implements utilitary methods for an instantiable
 * @link{josemmo\Facturae\Facturae}.
 */
abstract class FacturaeUtils extends FacturaeProperties {

  /**
   * Generate random ID
   *
   * This method is used for generating random IDs required when signing the
   * document.
   *
   * @return int Random number
   */
  protected function random() {
    if (function_exists('random_int')) {
      return random_int(0x10000000, 0x7FFFFFFF);
    } else {
      return rand(100000, 999999);
    }
  }


  /**
   * Pad
   *
   * @param  float       $val   Input value
   * @param  string|null $field Field
   * @return string             Padded value
   */
  protected function pad($val, $field=null) {
    // Get decimals
    $vKey = isset(self::$DECIMALS[$this->version]) ? $this->version : null;
    $decimals = self::$DECIMALS[$vKey];
    if (!isset($decimals[$field])) $field = null;
    $decimals = $decimals[$field];

    // Pad value
    $res = number_format(round($val, $decimals['max']), $decimals['max'], ".", "");
    for ($i=0; $i<$decimals['max']-$decimals['min']; $i++) {
      if (substr($res, -1) !== "0") break;
      $res = substr($res, 0, -1);
    }
    return $res;
  }


  /**
   * Get XML NameSpaces
   *
   * NOTE: Should be defined in alphabetical order
   *
   * @return string XML NameSpaces
   */
  protected function getNamespaces() {
    $xmlns = array();
    $xmlns[] = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"';
    $xmlns[] = 'xmlns:fe="' . self::$SCHEMA_NS[$this->version] . '"';
    $xmlns[] = 'xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"';
    $xmlns = implode(' ', $xmlns);
    return $xmlns;
  }

}
