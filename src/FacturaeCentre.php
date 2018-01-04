<?php

namespace josemmo\Facturae;

/**
 * Facturae Administrative Centre
 *
 * Represents an administrative centre, which can be linked to a party.
 */
class FacturaeCentre {

  const ROLE_CONTABLE = "01";
  const ROLE_FISCAL = "01";
  const ROLE_GESTOR = "02";
  const ROLE_RECEPTOR = "02";
  const ROLE_TRAMITADOR = "03";
  const ROLE_PAGADOR = "03";
  const ROLE_PROPONENTE = "04";

  public $code = NULL;
  public $role = NULL;

  public $name = NULL;
  public $firstSurname = NULL;
  public $lastSurname = NULL;
  public $description = NULL;

  public $address = NULL;
  public $postCode = NULL;
  public $town = NULL;
  public $province = NULL;
  public $countryCode = "ESP";


  /**
   * Construct
   * 
   * @param array $properties Party properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;
  }

}
