<?php
namespace josemmo\Facturae\Controller\Extensions;

abstract class Extension {

  private $fac;


  /**
   * Constructor function
   * @param Facturae $fac Parent invoice object
   */
  public function __construct($fac) {
    $this->fac = $fac;
  }


  /**
   * Get invoice
   * @return Facturae Parent invoice object
   */
  protected function getInvoice() {
    return $this->fac;
  }


  /**
   * Get additional namespaces
   * @return string[] Additional Namespaces
   */
  public function __getNamespaces() {
    return array();
  }


  /**
   * On before sign
   * @param  string $xml Input XML
   * @return string      Output XML
   */
  public function __onBeforeSign($xml) {
    return $xml;
  }


  /**
   * On after sign
   * @param  string $xml Input XML
   * @return string      Output XML
   */
  public function __onAfterSign($xml) {
    return $xml;
  }

}
