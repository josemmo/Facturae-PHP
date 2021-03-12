<?php
namespace josemmo\Facturae;

use josemmo\Facturae\Common\XmlTools;

/**
 * Facturae Party
 *
 * Represents a party, which is an entity defined by Facturae that can be
 * the seller or the buyer of an invoice.
 */
class FacturaeParty {

  public $isLegalEntity = true; // By default is a company and not a person
  public $taxNumber = null;
  public $name = null;

  // This block is only used for legal entities
  public $book = null;                        // "Libro"
  public $registerOfCompaniesLocation = null; // "Registro mercantil"
  public $sheet = null;                       // "Hoja"
  public $folio = null;                       // "Folio"
  public $section = null;                     // "Sección"
  public $volume = null;                      // "Tomo"

  // This block is only required for individuals
  public $firstSurname = null;
  public $lastSurname = null;

  public $address = null;
  public $postCode = null;
  public $town = null;
  public $province = null;
  public $countryCode = "ESP";

  public $email = null;
  public $phone = null;
  public $fax = null;
  public $website = null;

  public $contactPeople = null;
  public $cnoCnae = null;
  public $ineTownCode = null;
  public $centres = array();


  /**
   * Construct
   *
   * @param array $properties Party properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;
    if (isset($this->merchantRegister)) {
      $this->registerOfCompaniesLocation = $this->merchantRegister;
    }
  }


  /**
   * Get XML
   *
   * @param  string $schema Facturae schema version
   * @return string         Entity as Facturae XML
   */
  public function getXML($schema) {
    $tools = new XmlTools();

    // Add tax identification
    $xml = '<TaxIdentification>' .
             '<PersonTypeCode>' . ($this->isLegalEntity ? 'J' : 'F') . '</PersonTypeCode>' .
             '<ResidenceTypeCode>R</ResidenceTypeCode>' .
             '<TaxIdentificationNumber>' . $tools->escape($this->taxNumber) . '</TaxIdentificationNumber>' .
           '</TaxIdentification>';

    // Add administrative centres
    if (count($this->centres) > 0) {
      $xml .= '<AdministrativeCentres>';
      foreach ($this->centres as $centre) {
        $xml .= '<AdministrativeCentre>';
        $xml .= '<CentreCode>' . $centre->code . '</CentreCode>';
        $xml .= '<RoleTypeCode>' . $centre->role . '</RoleTypeCode>';
        $xml .= '<Name>' . $tools->escape($centre->name) . '</Name>';
        if (!is_null($centre->firstSurname)) {
          $xml .= '<FirstSurname>' . $tools->escape($centre->firstSurname) . '</FirstSurname>';
        }
        if (!is_null($centre->lastSurname)) {
          $xml .= '<SecondSurname>' . $tools->escape($centre->lastSurname) . '</SecondSurname>';
        }

        // Get centre address, else use fallback
        $addressTarget = $centre;
        foreach (['address', 'postCode', 'town', 'province', 'countryCode'] as $field) {
          if (empty($centre->{$field})) {
            $addressTarget = $this;
            break;
          }
        }

        if ($addressTarget->countryCode === "ESP") {
          $xml .= '<AddressInSpain>' .
            '<Address>' . $tools->escape($addressTarget->address) . '</Address>' .
            '<PostCode>' . $addressTarget->postCode . '</PostCode>' .
            '<Town>' . $tools->escape($addressTarget->town) . '</Town>' .
            '<Province>' . $tools->escape($addressTarget->province) . '</Province>' .
            '<CountryCode>' . $addressTarget->countryCode . '</CountryCode>' .
            '</AddressInSpain>';
        } else {
          $xml .= '<OverseasAddress>' .
            '<Address>' . $tools->escape($addressTarget->address) . '</Address>' .
            '<PostCodeAndTown>' . $addressTarget->postCode . ' ' . $tools->escape($addressTarget->town) . '</PostCodeAndTown>' .
            '<Province>' . $tools->escape($addressTarget->province) . '</Province>' .
            '<CountryCode>' . $addressTarget->countryCode . '</CountryCode>' .
            '</OverseasAddress>';
        }

        if (!is_null($centre->description)) {
          $xml .= '<CentreDescription>' . $tools->escape($centre->description) . '</CentreDescription>';
        }
        $xml .= '</AdministrativeCentre>';
      }
      $xml .= '</AdministrativeCentres>';
    }

    // Add custom block (either `LegalEntity` or `Individual`)
    $xml .= $this->isLegalEntity ? '<LegalEntity>' : '<Individual>';

    // Add data exclusive to `LegalEntity`
    if ($this->isLegalEntity) {
      $xml .= '<CorporateName>' . $tools->escape($this->name) . '</CorporateName>';
      $fields = array("book", "registerOfCompaniesLocation", "sheet", "folio",
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
      $xml .= '<Name>' . $tools->escape($this->name) . '</Name>';
      $xml .= '<FirstSurname>' . $tools->escape($this->firstSurname) . '</FirstSurname>';
      $xml .= '<SecondSurname>' . $tools->escape($this->lastSurname) . '</SecondSurname>';
    }

    // Add address
    if ($this->countryCode === "ESP") {
      $xml .= '<AddressInSpain>' .
        '<Address>' . $tools->escape($this->address) . '</Address>' .
        '<PostCode>' . $this->postCode . '</PostCode>' .
        '<Town>' . $tools->escape($this->town) . '</Town>' .
        '<Province>' . $tools->escape($this->province) . '</Province>' .
        '<CountryCode>' . $this->countryCode . '</CountryCode>' .
        '</AddressInSpain>';
    } else {
      $xml .= '<OverseasAddress>' .
        '<Address>' . $tools->escape($this->address) . '</Address>' .
        '<PostCodeAndTown>' . $this->postCode . ' ' . $tools->escape($this->town) . '</PostCodeAndTown>' .
        '<Province>' . $tools->escape($this->province) . '</Province>' .
        '<CountryCode>' . $this->countryCode . '</CountryCode>' .
        '</OverseasAddress>';
    }
    // Add contact details
    $xml .= $this->getContactDetailsXML();

    // Close custom block
    $xml .= ($this->isLegalEntity) ? '</LegalEntity>' : '</Individual>';

    // Return data
    return $xml;
  }


  /**
   * Get contact details XML
   *
   * @return string Contact details XML
   */
  private function getContactDetailsXML() {
    $tools = new XmlTools();
    $contactFields = [
      "phone" => "Telephone",
      "fax" => "TeleFax",
      "website" => "WebAddress",
      "email" => "ElectronicMail",
      "contactPeople" => "ContactPersons",
      "cnoCnae" => "CnoCnae",
      "ineTownCode" => "INETownCode"
    ];

    // Validate attributes
    $hasDetails = false;
    foreach (array_keys($contactFields) as $field) {
      if (!empty($this->$field)) {
        $hasDetails = true;
        break;
      }
    }
    if (!$hasDetails) return "";

    // Add fields
    $xml = '<ContactDetails>';
    foreach ($contactFields as $field=>$xmlName) {
      $value = $this->$field;
      if (!empty($value)) {
        $xml .= "<$xmlName>" . $tools->escape($value) . "</$xmlName>";
      }
    }
    $xml .= '</ContactDetails>';

    return $xml;
  }

}

