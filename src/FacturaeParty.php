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
   *
   * @param array $properties Party properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;
  }


  /**
   * Get XML
   *
   * @param  string $schema Facturae schema version
   * @return string         Entity as Facturae XML
   */
  public function getXML($schema) {
    // Add tax identification
    $xml = '<TaxIdentification>' .
             '<PersonTypeCode>' . ($this->isLegalEntity ? 'J' : 'F') . '</PersonTypeCode>' .
             '<ResidenceTypeCode>R</ResidenceTypeCode>' .
             '<TaxIdentificationNumber>' . $this->taxNumber . '</TaxIdentificationNumber>' .
           '</TaxIdentification>';

    // Add administrative centres
    if (count($this->centres) > 0) {
      $xml .= '<AdministrativeCentres>';
      foreach ($this->centres as $centre) {
        $xml .= '<AdministrativeCentre>';
        $xml .= '<CentreCode>' . $centre->code . '</CentreCode>';
        $xml .= '<RoleTypeCode>' . $centre->role . '</RoleTypeCode>';
        $xml .= '<Name>' . $centre->name . '</Name>';
        if (!is_null($centre->firstSurname)) {
          $xml .= '<FirstSurname>' . $centre->firstSurname . '</FirstSurname>';
        }
        if (!is_null($centre->lastSurname)) {
          $xml .= '<SecondSurname>' . $centre->lastSurname . '</SecondSurname>';
        }
        $xml .= '<AddressInSpain>' .
                  '<Address>' . $this->address . '</Address>' .
                  '<PostCode>' . $this->postCode . '</PostCode>' .
                  '<Town>' . $this->town .'</Town>' .
                  '<Province>' . $this->province . '</Province>' .
                  '<CountryCode>' . $this->countryCode . '</CountryCode>' .
                '</AddressInSpain>';
        if (!is_null($centre->description)) {
          $xml .= '<CentreDescription>' . $centre->description . '</CentreDescription>';
        }
        $xml .= '</AdministrativeCentre>';
      }
      $xml .= '</AdministrativeCentres>';
    }

    // Add custom block (either `LegalEntity` or `Individual`)
    $xml .= ($this->isLegalEntity) ? '<LegalEntity>' : '<Individual>';

    // Add data exclusive to `LegalEntity`
    if ($this->isLegalEntity) {
      $xml .= '<CorporateName>' . $this->name . '</CorporateName>';
      $fields = array("book", "merchantRegister", "sheet", "folio",
        "section", "volume");

      $nonEmptyFields = array();
      foreach ($fields as $fieldName) {
        if (!empty($this->{$fieldName})) $nonEmptyFields[] = $fieldName;
      }

      if (count($nonEmptyFields) > 0) {
        $xml .= '<RegistrationData>';
        foreach ($nonEmptyFields as $fieldName) {
          $tag = ucfirst($fieldName);
          $xml .= "<$tag>" . $this->{$fieldName} . "</$tag>";
        }
        $xml .= '</RegistrationData>';
      }
    }

    // Add data exclusive to `Individual`
    if (!$this->isLegalEntity) {
      $xml .= '<Name>' . $this->name . '</Name>';
      $xml .= '<FirstSurname>' . $this->firstSurname . '</FirstSurname>';
      $xml .= '<SecondSurname>' . $this->lastSurname . '</SecondSurname>';
    }

    // Add address
    $xml .= '<AddressInSpain>' .
              '<Address>' . $this->address . '</Address>' .
              '<PostCode>' . $this->postCode . '</PostCode>' .
              '<Town>' . $this->town . '</Town>' .
              '<Province>' . $this->province . '</Province>' .
              '<CountryCode>' . $this->countryCode . '</CountryCode>' .
            '</AddressInSpain>';

    // Close custom block
    $xml .= ($this->isLegalEntity) ? '</LegalEntity>' : '</Individual>';

    // Return data
    return $xml;
  }

}
