<?php
namespace josemmo\Facturae\FacturaeTraits;

/**
 * Implements utilitary methods for an instantiable Facturae.
 */
trait UtilsTrait {
  protected $extensions = array();

  /**
   * Is withheld tax
   * This method returns if a tax type is, by default, a withheld tax.
   * @param  string  $taxCode Tax
   * @return boolean          Is withheld
   */
  public static function isWithheldTax($taxCode) {
    return in_array($taxCode, [self::TAX_IRPF]);
  }


  /**
   * Pad amount
   * @param  float       $val   Input value
   * @param  string|null $field Field
   * @return string             Padded value
   */
  public function pad($val, $field=null) {
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
   * Get XML Namespaces
   * @return string[] XML Namespaces
   */
  protected function getNamespaces() {
    $xmlns = array();
    $xmlns[] = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"';
    $xmlns[] = 'xmlns:fe="' . self::$SCHEMA_NS[$this->version] . '"';
    $xmlns[] = 'xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"';
    return $xmlns;
  }


  /**
   * Get extension
   * @param  string    $name Extension name or class name
   * @return Extension       Extension instance
   */
  public function getExtension($name) {
    $topNamespace = explode('\\', __NAMESPACE__);
    array_pop($topNamespace);
    $topNamespace = implode('\\', $topNamespace);

    // Get extension from invoice instance
    if (isset($this->extensions[$name])) {
      return $this->extensions[$name];
    }

    // Instantiate extension
    $classPath = "$topNamespace\\Extensions\\{$name}Extension";
    if (!class_exists($classPath)) $classPath = $name;
    $this->extensions[$name] = new $classPath($this);
    return $this->extensions[$name];
  }

}
