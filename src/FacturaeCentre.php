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

  const ROLE_B2B_FISCAL = "Fiscal";
  const ROLE_B2B_PAYER = "Payer";
  const ROLE_B2B_BUYER = "Buyer";
  const ROLE_B2B_COLLECTOR = "Collector";
  const ROLE_B2B_SELLER = "Seller";
  const ROLE_B2B_PAYMENT_RECEIVER = "Payment receiver";
  const ROLE_B2B_COLLECTION_RECEIVER = "Collection receiver";
  const ROLE_B2B_ISSUER = "Issuer";

  public $code = null;
  public $role = null;

  public $name = null;
  public $firstSurname = null;
  public $lastSurname = null;
  public $description = null;

  public $address = null;
  public $postCode = null;
  public $town = null;
  public $province = null;
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
