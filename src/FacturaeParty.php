<?php

namespace josemmo\Facturae;

/**
 * Facturae Party
 *
 * Represents a party, which is an entity defined by Facturae that can be
 * the seller or the buyer of an invoice.
 */
class FacturaeParty {
  public $isLegalEntity = true;    // By default is a company and not a person
  public $taxNumber = NULL;
  public $name = NULL;

  // This block is only used for legal entities
  public $book = NULL;             // "Libro"
  public $merchantRegister = NULL; // "Registro mercantil"
  public $sheet = NULL;            // "Hoja"
  public $folio = NULL;            // "Folio"
  public $section = NULL;          // "Sección"
  public $volume = NULL;           // "Tomo"

  // This block is only required for individuals
  public $firstSurname = NULL;
  public $lastSurname = NULL;

  public $address = NULL;
  public $postCode = NULL;
  public $town = NULL;
  public $province = NULL;
  public $countryCode = "ESP";

  public $email = NULL;
  public $phone = NULL;
  public $fax = NULL;
  public $website = NULL;

  public $contactPeople = NULL;
  public $cnoCnae = NULL;
  public $ineTownCode = NULL;
  public $centres = array();


  /**
   * Construct
   * @param array $properties Party properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;
  }


  /**
   * Get XML
   * @param  string $schema Facturae schema version
   * @return string         Entity as Facturae XML
   */
  public function getXML($schema) {
    // Add tax identification
    $xml = '<TaxIdentification><PersonTypeCode>' .
      ($this->isLegalEntity ? 'J' : 'F') . '</PersonTypeCode>' .
      '<ResidenceTypeCode>R</ResidenceTypeCode><TaxIdentificationNumber>' .
      $this->taxNumber . '</TaxIdentificationNumber></TaxIdentification>';

    // Add administrative centres
    if (count($this->centres) > 0) {
      $xml .= '<AdministrativeCentres>';
      foreach ($this->centres as $centre) {
        $xml .= '<AdministrativeCentre><CentreCode>' . $centre->code .
        '</CentreCode><RoleTypeCode>' . $centre->role . '</RoleTypeCode>' .
        '<Name>' . $centre->name . '</Name>';
        if (!is_null($centre->firstSurname)) $xml .= '<FirstSurname>' .
          $centre->firstSurname . '</FirstSurname>';
        if (!is_null($centre->lastSurname)) $xml .= '<SecondSurname>' .
          $centre->lastSurname . '</SecondSurname>';
        $xml .= '<AddressInSpain><Address>' . $this->address . '</Address>' .
          '<PostCode>' . $this->postCode . '</PostCode><Town>' . $this->town .
          '</Town><Province>' . $this->province . '</Province><CountryCode>' .
          $this->countryCode . '</CountryCode></AddressInSpain>';
        if (!is_null($centre->description)) $xml .= '<CentreDescription>' .
          $centre->description . '</CentreDescription>';
        $xml .= '</AdministrativeCentre>';
      }
      $xml .= '</AdministrativeCentres>';
    }

    // Add custom block
    $xml .= ($this->isLegalEntity) ? '<LegalEntity>' : '<Individual>';

    // Add legal entity data
    if ($this->isLegalEntity) {
      $xml .= '<CorporateName>' . $this->name . '</CorporateName>';
      if (
        !is_null($this->book) || !is_null($this->merchantRegister) ||
        !is_null($this->sheet) || !is_null($this->folio) ||
        !is_null($this->section) || !is_null($this->volume)
      ) {
        $xml .= '<RegistrationData>';
        if (!is_null($this->book)) $xml .= '<Book>' . $this->book . '</Book>';
        if (!is_null($this->merchantRegister)) $xml .=
          '<RegisterOfCompaniesLocation>' . $this->merchantRegister .
          '</RegisterOfCompaniesLocation>';
        if (!is_null($this->sheet)) $xml .= '<Sheet>' . $this->sheet . '</Sheet>';
        if (!is_null($this->folio)) $xml .= '<Folio>' . $this->folio . '</Folio>';
        if (!is_null($this->section)) $xml .= '<Section>' . $this->section .
          '</Section>';
        if (!is_null($this->volume)) $xml .= '<Volume>' . $this->volume .
          '</Volume>';
        $xml .= '</RegistrationData>';
      }
    }

    // Add individual data
    if (!$this->isLegalEntity) {
      $xml .= '<Name>' . $this->name . '</Name><FirstSurname>' .
        $this->firstSurname . '</FirstSurname><SecondSurname>' .
        $this->lastSurname . '</SecondSurname>';
    }

    // Add address
    $xml .= '<AddressInSpain><Address>' . $this->address . '</Address>' .
      '<PostCode>' . $this->postCode . '</PostCode><Town>' . $this->town .
      '</Town><Province>' . $this->province . '</Province><CountryCode>' .
      $this->countryCode . '</CountryCode></AddressInSpain>';

    // Close custom block
    $xml .= ($this->isLegalEntity) ? '</LegalEntity>' : '</Individual>';

    // Return data
    return $xml;
  }

}