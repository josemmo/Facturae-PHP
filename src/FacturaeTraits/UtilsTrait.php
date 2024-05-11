<?php
namespace josemmo\Facturae\FacturaeTraits;

use josemmo\Facturae\Extensions\FacturaeExtension;
use josemmo\Facturae\Facturae;

/**
 * Implements utility methods for an instantiable Facturae.
 *
 * @var Facturae $this
 */
trait UtilsTrait {
  /** @var FacturaeExtension[] */
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
   * @param  float    $val       Input value
   * @param  string   $field     Field
   * @param  int|null $precision Precision on which to pad amount, `null` for always
   * @return string              Padded value (or input value if precision unmet)
   */
  public function pad($val, $field, $precision=null) {
    // Do not pad if precision unmet
    if (!is_null($precision) && $precision !== $this->precision) {
      return $val;
    }

    // Get decimals
    $decimals = isset(self::$DECIMALS[$this->version]) ? self::$DECIMALS[$this->version] : self::$DECIMALS[''];
    $decimals = isset($decimals[$field]) ? $decimals[$field] : $decimals[''];

    // Pad value
    $res = number_format($val, $decimals['max'], '.', '');
    for ($i=0; $i<$decimals['max']-$decimals['min']; $i++) {
      if (substr($res, -1) !== "0") break;
      $res = substr($res, 0, -1);
    }
    $res = rtrim($res, '.');
    return $res;
  }


  /**
   * Get extension
   * @param  string            $name Extension name or class name
   * @return FacturaeExtension       Extension instance
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
