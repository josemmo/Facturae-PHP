<?php
namespace josemmo\Facturae\Controller;

use josemmo\Facturae\FacturaeItem;

/**
 * Implements all attributes and methods needed to make
 * @link{josemmo\Facturae\Facturae} instantiable.
 *
 * This includes all properties that define an electronic invoice, but without
 * additional functionalities such as signing or exporting.
 */
abstract class FacturaeProperties extends FacturaeConstants {

  /* ATTRIBUTES */
  protected $currency = "EUR";
  protected $language = "es";
  protected $version = null;
  protected $header = array(
    "serie" => null,
    "number" => null,
    "issueDate" => null,
    "dueDate" => null,
    "startDate" => null,
    "endDate" => null,
    "paymentMethod" => null,
    "paymentIBAN" => null,
    "description" => null,
    "receiverTransactionReference" => null,
    "fileReference" => null,
    "receiverContractReference" => null
  );
  protected $parties = array(
    "seller" => null,
    "buyer" => null
  );
  protected $items = array();
  protected $legalLiterals = array();


  /**
   * Constructor for the class
   * @param string $schemaVersion If omitted, latest version available
   */
  public function __construct($schemaVersion=self::SCHEMA_3_2_1) {
    $this->setSchemaVersion($schemaVersion);
  }


  /**
   * Set schema version
   *
   * @param string $schemaVersion Facturae schema version to use
   */
  public function setSchemaVersion($schemaVersion) {
    $this->version = $schemaVersion;
  }


  /**
   * Set seller
   *
   * @param FacturaeParty $seller Seller information
   */
  public function setSeller($seller) {
    $this->parties['seller'] = $seller;
  }


  /**
   * Set buyer
   *
   * @param FacturaeParty $buyer Buyer information
   */
  public function setBuyer($buyer) {
    $this->parties['buyer'] = $buyer;
  }


  /**
   * Set invoice number
   *
   * @param string     $serie  Serie code of the invoice
   * @param int|string $number Invoice number in given serie
   */
  public function setNumber($serie, $number) {
    $this->header['serie'] = $serie;
    $this->header['number'] = $number;
  }


  /**
   * Set issue date
   *
   * @param int|string $date Issue date
   */
  public function setIssueDate($date) {
    $this->header['issueDate'] = is_string($date) ? strtotime($date) : $date;
  }


  /**
   * Set due date
   *
   * @param int|string $date Due date
   */
  public function setDueDate($date) {
    $this->header['dueDate'] = is_string($date) ? strtotime($date) : $date;
  }


  /**
   * Set billing period
   *
   * @param int|string $date Start date
   * @param int|string $date End date
   */
  public function setBillingPeriod($startDate=null, $endDate=null) {
    if (is_string($startDate)) $startDate = strtotime($startDate);
    if (is_string($endDate)) $endDate = strtotime($endDate);
    $this->header['startDate'] = $startDate;
    $this->header['endDate'] = $endDate;
  }


  /**
   * Set dates
   *
   * This is a shortcut for setting both issue and due date in a single line
   *
   * @param int|string $issueDate Issue date
   * @param int|string $dueDate Due date
   */
  public function setDates($issueDate, $dueDate=null) {
    $this->setIssueDate($issueDate);
    $this->setDueDate($dueDate);
  }


  /**
   * Set payment method
   *
   * @param string $method Payment method
   * @param string $iban   Bank account in case of bank transfer
   */
  public function setPaymentMethod($method=self::PAYMENT_CASH, $iban=null) {
    $this->header['paymentMethod'] = $method;
    if (!is_null($iban)) $iban = str_replace(" ", "", $iban);
    $this->header['paymentIBAN'] = $iban;
  }


  /**
   * Set description
   * @param string $desc Invoice description
   */
  public function setDescription($desc) {
    $this->header['description'] = $desc;
  }


  /**
   * Set references
   * @param string $file        File reference
   * @param string $transaction Transaction reference
   * @param string $contract    Contract reference
   */
  public function setReferences($file, $transaction=null, $contract=null) {
    $this->header['fileReference'] = $file;
    $this->header['receiverTransactionReference'] = $transaction;
    $this->header['receiverContractReference'] = $contract;
  }


  /**
   * Add legal literal
   *
   * @param string $message Legal literal reference
   */
  public function addLegalLiteral($message) {
    $this->legalLiterals[] = $message;
  }


  /**
   * Add item
   *
   * Adds an item row to invoice. The fist parameter ($desc), can be an string
   * representing the item description or a 2 element array containing the item
   * description and an additional string of information.
   *
   * @param FacturaeItem|string|array $desc      Item to add or description
   * @param float                     $unitPrice Price per unit, taxes included
   * @param float                     $quantity  Quantity
   * @param int                       $taxType   Tax type
   * @param float                     $taxRate   Tax rate
   */
  public function addItem($desc, $unitPrice=null, $quantity=1, $taxType=null,
                          $taxRate=null) {
    if ($desc instanceOf FacturaeItem) {
      $item = $desc;
    } else {
      $item = new FacturaeItem([
        "name" => is_array($desc) ? $desc[0] : $desc,
        "description" => is_array($desc) ? $desc[1] : null,
        "quantity" => $quantity,
        "unitPrice" => $unitPrice,
        "taxes" => array($taxType => $taxRate)
      ]);
    }
    array_push($this->items, $item);
  }


  /**
   * Get totals
   *
   * @return array Invoice totals
   */
  public function getTotals() {
    // Define starting values
    $totals = array(
      "taxesOutputs" => array(),
      "taxesWithheld" => array(),
      "invoiceAmount" => 0,
      "grossAmount" => 0,
      "generalDiscounts" => 0, // TODO: implement general discounts
      "generalCharges" => 0,   // TODO: implement general surcharges
      "totalTaxesOutputs" => 0,
      "totalTaxesWithheld" => 0
    );

    // Run through every item
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData($this);
      $totals['grossAmount'] += $item['grossAmount'];
      $totals['totalTaxesOutputs'] += $item['totalTaxesOutputs'];
      $totals['totalTaxesWithheld'] += $item['totalTaxesWithheld'];

      // Get taxes
      foreach (["taxesOutputs", "taxesWithheld"] as $taxGroup) {
        foreach ($item[$taxGroup] as $type=>$tax) {
          if (!isset($totals[$taxGroup][$type])) {
            $totals[$taxGroup][$type] = array();
          }
          if (!isset($totals[$taxGroup][$type][$tax['rate']])) {
            $totals[$taxGroup][$type][$tax['rate']] = array(
              "base" => 0,
              "amount" => 0
            );
          }
          $totals[$taxGroup][$type][$tax['rate']]['base'] += $tax['base'];
          $totals[$taxGroup][$type][$tax['rate']]['amount'] += $tax['amount'];
        }
      }
    }

    // Normalize values
    $totals['grossAmount'] = $this->pad($totals['grossAmount']);
    $totals['totalTaxesOutputs'] = $this->pad($totals['totalTaxesOutputs']);
    $totals['totalTaxesWithheld'] = $this->pad($totals['totalTaxesWithheld']);
    $totals['generalDiscounts'] = $this->pad($totals['generalDiscounts']);
    $totals['generalCharges'] = $this->pad($totals['generalCharges']);

    // Fill rest of values
    $totals['grossAmountBeforeTaxes'] = $totals['grossAmount'] -
      $totals['generalDiscounts'] + $totals['generalCharges'];
    $totals['invoiceAmount'] = $this->pad($totals['grossAmount'] +
      $totals['totalTaxesOutputs'] - $totals['totalTaxesWithheld']);

    return $totals;
  }

}
