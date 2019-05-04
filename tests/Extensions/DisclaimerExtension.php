<?php
namespace josemmo\Facturae\Tests\Extensions;

use josemmo\Facturae\Extensions\FacturaeExtension;

class DisclaimerExtension extends FacturaeExtension {
  private $enabled = false;

  /**
   * Enable extension
   */
  public function enable() {
    $this->enabled = true;
  }


  /**
   * Get disclaimer text
   * @return string Disclaimer
   */
  public function getDisclaimer() {
    return "--- Este es un mensaje añadido automáticamente por una extensión externa ---";
  }


  /**
   * @inheritdoc
   */
  public function __onBeforeExport() {
    if ($this->enabled) {
      $disclaimer = $this->getDisclaimer();
      $this->getInvoice()->addLegalLiteral($disclaimer);
    }
  }

}
