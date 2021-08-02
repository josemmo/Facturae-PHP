<?php
namespace josemmo\Facturae\Face\Traits;

trait StageableTrait {
  private $production = true;

  /**
   * Set production environment
   * @param boolean $production Is production
   */
  public function setProduction($production) {
    $this->production = $production;
  }


  /**
   * Is production
   * @return boolean Is production
   */
  public function isProduction() {
    return $this->production;
  }
}
