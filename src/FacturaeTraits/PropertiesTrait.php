<?php
namespace josemmo\Facturae\FacturaeTraits;

use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaePayment;

/**
 * Implements all attributes and methods needed to make Facturae instantiable.
 * This includes all properties that define an electronic invoice, but without
 * additional functionalities such as signing or exporting.
 */
trait PropertiesTrait {
  protected $currency = "EUR";
  protected $language = "es";
  protected $version = null;
  protected $precision = self::PRECISION_LINE;
  protected $header = array(
    "serie" => null,
    "number" => null,
    "issueDate" => null,
    "startDate" => null,
    "endDate" => null,
    "assignmentClauses" => null,
    "description" => null,
    "receiverTransactionReference" => null,
    "fileReference" => null,
    "receiverContractReference" => null,
    "relatedInvoice" => null,
    "additionalInformation" => null
  );
  protected $parties = array(
    "assignee" => null,
    "seller" => null,
    "buyer" => null
  );
  protected $items = array();
  protected $legalLiterals = array();
  protected $discounts = array();
  protected $charges = array();
  protected $attachments = array();
  /** @var FacturaePayment[] */
  protected $payments = array();


  /**
   * Constructor for the class
   * @param string $schemaVersion If omitted, latest version available
   */
  public function __construct($schemaVersion=self::SCHEMA_3_2_1) {
    $this->setSchemaVersion($schemaVersion);
  }


  /**
   * Set schema version
   * @param  string   $schemaVersion FacturaE schema version to use
   * @return Facturae                Invoice instance
   */
  public function setSchemaVersion($schemaVersion) {
    $this->version = $schemaVersion;
    return $this;
  }


  /**
   * Get schema version
   * @return string FacturaE schema version to use
   */
  public function getSchemaVersion() {
    return $this->version;
  }


  /**
   * Get rounding precision
   * @return int Rounding precision
   */
  public function getPrecision() {
    return $this->precision;
  }


  /**
   * Set rounding precision
   * @param  int      $precision Rounding precision
   * @return Facturae            Invoice instance
   */
  public function setPrecision($precision) {
    $this->precision = $precision;
    return $this;
  }


  /**
   * Set assignee
   * @param  FacturaeParty $assignee Assignee information
   * @return Facturae                Invoice instance
   */
  public function setAssignee($assignee) {
    $this->parties['assignee'] = $assignee;
    return $this;
  }


  /**
   * Get assignee
   * @return FacturaeParty|null Assignee information
   */
  public function getAssignee() {
    return $this->parties['assignee'];
  }


  /**
   * Set assignment clauses
   * @param  string   $clauses Assignment clauses
   * @return Facturae          Invoice instance
   */
  public function setAssignmentClauses($clauses) {
    $this->header['assignmentClauses'] = $clauses;
    return $this;
  }


  /**
   * Get assignment clauses
   * @return string|null Assignment clauses
   */
  public function getAssignmentClauses() {
    return $this->header['assignmentClauses'];
  }


  /**
   * Set seller
   * @param  FacturaeParty $seller Seller information
   * @return Facturae              Invoice instance
   */
  public function setSeller($seller) {
    $this->parties['seller'] = $seller;
    return $this;
  }


  /**
   * Get seller
   * @return FacturaeParty|null Seller information
   */
  public function getSeller() {
    return $this->parties['seller'];
  }


  /**
   * Set buyer
   * @param  FacturaeParty $buyer Buyer information
   * @return Facturae             Invoice instance
   */
  public function setBuyer($buyer) {
    $this->parties['buyer'] = $buyer;
    return $this;
  }


  /**
   * Get buyer
   * @return FacturaeParty|null Buyer information
   */
  public function getBuyer() {
    return $this->parties['buyer'];
  }


  /**
   * Set invoice number
   * @param  string     $serie  Serie code of the invoice
   * @param  int|string $number Invoice number in given serie
   * @return Facturae           Invoice instance
   */
  public function setNumber($serie, $number) {
    $this->header['serie'] = $serie;
    $this->header['number'] = $number;
    return $this;
  }


  /**
   * Get invoice number
   * @return array Serie code and invoice number
   */
  public function getNumber() {
    return array(
      "serie" => $this->header['serie'],
      "number" => $this->header['number']
    );
  }


  /**
   * Set issue date
   * @param  int|string $date Issue date
   * @return Facturae         Invoice instance
   */
  public function setIssueDate($date) {
    $this->header['issueDate'] = is_string($date) ? strtotime($date) : $date;
    return $this;
  }


  /**
   * Get issue date
   * @return int|null Issue timestamp
   */
  public function getIssueDate() {
    return $this->header['issueDate'];
  }


  /**
   * Set due date
   * @param  int|string $date Due date
   * @return Facturae         Invoice instance
   * @deprecated 1.7.2 Due date is now associated to payment information.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function setDueDate($date) {
    if (empty($this->payments)) {
      $this->payments[] = new FacturaePayment();
    }
    $this->payments[0]->dueDate = $date;
    return $this;
  }


  /**
   * Get due date
   * @return int|null Due timestamp
   * @deprecated 1.7.2 Due date is now associated to payment information.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function getDueDate() {
    return empty($this->payments) ?
      null :
      (is_string($this->payments[0]->dueDate) ? strtotime($this->payments[0]->dueDate) : $this->payments[0]->dueDate);
  }


  /**
   * Set billing period
   * @param  int|string $date Start date
   * @param  int|string $date End date
   * @return Facturae         Invoice instance
   */
  public function setBillingPeriod($startDate, $endDate) {
    if (is_string($startDate)) $startDate = strtotime($startDate);
    if (is_string($endDate)) $endDate = strtotime($endDate);
    $this->header['startDate'] = $startDate;
    $this->header['endDate'] = $endDate;
    return $this;
  }


  /**
   * Get billing period
   * @return array Start and end dates for billing period
   */
  public function getBillingPeriod() {
    return array(
      "startDate" => $this->header['startDate'],
      "endDate" => $this->header['endDate']
    );
  }


  /**
   * Set dates
   * This is a shortcut for setting both issue and due date in a single line.
   * @param  int|string $issueDate Issue date
   * @param  int|string $dueDate   Due date
   * @return Facturae              Invoice instance
   * @deprecated 1.7.2 Due date is now associated to payment information.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function setDates($issueDate, $dueDate=null) {
    $this->setIssueDate($issueDate);
    $this->setDueDate($dueDate);
    return $this;
  }


  /**
   * Add payment installment
   * @param  FacturaePayment $payment Payment details
   * @return Facturae                 Invoice instance
   */
  public function addPayment($payment) {
    $this->payments[] = $payment;
    return $this;
  }


  /**
   * Get payment installments
   * @return FacturaePayment[] Payment installments
   */
  public function getPayments() {
    return $this->payments;
  }


  /**
   * Set payment method
   * @param  string      $method Payment method
   * @param  string|null $iban   Bank account number (IBAN)
   * @param  string|null $bic    SWIFT/BIC code of bank account
   * @return Facturae            Invoice instance
   * @deprecated 1.7.2 Invoice can now have multiple payment installments, use `Facturae::addPayment()` instead.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function setPaymentMethod($method=FacturaePayment::TYPE_CASH, $iban=null, $bic=null) {
    if (empty($this->payments)) {
      $this->payments[] = new FacturaePayment();
    }
    $this->payments[0]->method = $method;
    $this->payments[0]->iban = $iban;
    $this->payments[0]->bic = $bic;
    return $this;
  }


  /**
   * Get payment method
   * @return string|null Payment method
   * @deprecated 1.7.2 Invoice can now have multiple payment installments, use `Facturae::getPayments()` instead.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function getPaymentMethod() {
    return empty($this->payments) ? null : $this->payments[0]->method;
  }


  /**
   * Get payment IBAN
   * @return string|null Payment bank account IBAN
   * @deprecated 1.7.2 Invoice can now have multiple payment installments, use `Facturae::getPayments()` instead.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function getPaymentIBAN() {
    return empty($this->payments) ? null : $this->payments[0]->iban;
  }


  /**
   * Get payment BIC
   * @return string|null Payment bank account BIC
   * @deprecated 1.7.2 Invoice can now have multiple payment installments, use `Facturae::getPayments()` instead.
   * @see https://josemmo.github.io/Facturae-PHP/propiedades/datos-del-pago.html
   */
  public function getPaymentBIC() {
    return empty($this->payments) ? null : $this->payments[0]->bic;
  }


  /**
   * Set description
   * @param  string   $desc Invoice description
   * @return Facturae       Invoice instance
   */
  public function setDescription($desc) {
    $this->header['description'] = $desc;
    return $this;
  }


  /**
   * Get description
   * @return string|null Invoice description
   */
  public function getDescription() {
    return $this->header['description'];
  }


  /**
   * Set references
   * @param  string   $file        File reference
   * @param  string   $transaction Transaction reference
   * @param  string   $contract    Contract reference
   * @return Facturae              Invoice instance
   */
  public function setReferences($file, $transaction=null, $contract=null) {
    $this->header['fileReference'] = $file;
    $this->header['receiverTransactionReference'] = $transaction;
    $this->header['receiverContractReference'] = $contract;
    return $this;
  }


  /**
   * Get file reference
   * @return string|null File reference
   */
  public function getFileReference() {
    return $this->header['fileReference'];
  }


  /**
   * Get transaction reference
   * @return string|null Transaction reference
   */
  public function getTransactionReference() {
    return $this->header['receiverTransactionReference'];
  }


  /**
   * Get contract reference
   * @return string|null Contract reference
   */
  public function getContractReference() {
    return $this->header['receiverContractReference'];
  }


  /**
   * Add legal literal
   * @param  string   $message Legal literal reference
   * @return Facturae          Invoice instance
   */
  public function addLegalLiteral($message) {
    $this->legalLiterals[] = $message;
    return $this;
  }


  /**
   * Get legal literals
   * @return string[] Legal literals
   */
  public function getLegalLiterals() {
    return $this->legalLiterals;
  }


  /**
   * Clear legal literals
   * @return Facturae Invoice instance
   */
  public function clearLegalLiterals() {
    $this->legalLiterals = array();
    return $this;
  }


  /**
   * Add general discount
   * @param  string   $reason       Discount reason
   * @param  float    $value        Discount percent or amount
   * @param  boolean  $isPercentage Whether value is percentage or not
   * @return Facturae               Invoice instance
   */
  public function addDiscount($reason, $value, $isPercentage=true) {
    $this->discounts[] = array(
      "reason" => $reason,
      "rate"   => $isPercentage ? $value : null,
      "amount" => $isPercentage ? null   : $value
    );
    return $this;
  }


  /**
   * Get general discounts
   * @return array Invoice general discounts
   */
  public function getDiscounts() {
    return $this->discounts;
  }


  /**
   * Clear general discounts
   * @return Facturae Invoice instance
   */
  public function clearDiscounts() {
    $this->discounts = array();
    return $this;
  }


  /**
   * Add general charge
   * @param  string   $reason       Charge reason
   * @param  float    $value        Charge percent or amount
   * @param  boolean  $isPercentage Whether value is percentage or not
   * @return Facturae               Invoice instance
   */
  public function addCharge($reason, $value, $isPercentage=true) {
    $this->charges[] = array(
      "reason" => $reason,
      "rate"   => $isPercentage ? $value : null,
      "amount" => $isPercentage ? null   : $value
    );
  }


  /**
   * Get general charges
   * @return array Invoice general charges
   */
  public function getCharges() {
    return $this->charges;
  }


  /**
   * Clear general charges
   * @return Facturae Invoice instance
   */
  public function clearCharges() {
    $this->charges = array();
    return $this;
  }


  /**
   * Set related invoice
   * @param  string   $relatedInvoice Related invoice number
   * @return Facturae                 Invoice instance
   */
  public function setRelatedInvoice($relatedInvoice) {
    $this->header['relatedInvoice'] = $relatedInvoice;
  }


  /**
   * Get related invoice
   * @return string|null Related invoice number
   */
  public function getRelatedInvoice() {
    return $this->header['relatedInvoice'];
  }


  /**
   * Set additional information
   * @param  string   $information Invoice additional information
   * @return Facturae              Invoice instance
   */
  public function setAdditionalInformation($information) {
    $this->header['additionalInformation'] = $information;
    return $this;
  }


  /**
   * Get additional information
   * @return string|null Additional information
   */
  public function getAdditionalInformation() {
    return $this->header['additionalInformation'];
  }


  /**
   * Add attachment
   * @param  string|FacturaeFile $file        File path or instance
   * @param  string|null         $description Document description
   * @return Facturae                         Invoice instance
   */
  public function addAttachment($file, $description=null) {
    if (is_string($file)) {
      $filePath = $file;
      $file = new FacturaeFile();
      $file->loadFile($filePath);
    }

    $this->attachments[] = array(
      "file" => $file,
      "description" => $description
    );
  }


  /**
   * Get attachments
   * @return array Attachments
   */
  public function getAttachments() {
    return $this->attachments;
  }


  /**
   * Clear attachments
   * @return Facturae Invoice instance
   */
  public function clearAttachments() {
    $this->attachments = array();
    return $this;
  }


  /**
   * Add item
   * Adds an item row to invoice. The fist parameter ($desc), can be an string
   * representing the item description or a 2 element array containing the item
   * description and an additional string of information.
   * @param  FacturaeItem|string|array $desc      Item to add or description
   * @param  float                     $unitPrice Price per unit, taxes included
   * @param  float                     $quantity  Quantity
   * @param  int                       $taxType   Tax type
   * @param  float                     $taxRate   Tax rate
   * @return Facturae                             Invoice instance
   */
  public function addItem($desc, $unitPrice=null, $quantity=1, $taxType=null, $taxRate=null) {
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
    $this->items[] = $item;
    return $this;
  }


  /**
   * Get invoice items
   * @return FacturaeItem[] Invoice items
   */
  public function getItems() {
    return $this->items;
  }


  /**
   * Clear invoice items
   * @return Facturae Invoice instance
   */
  public function clearItems() {
    $this->items = array();
    return $this;
  }


  /**
   * Get totals
   * @return array Invoice totals
   */
  public function getTotals() {
    // Define starting values
    $totals = array(
      "taxesOutputs" => array(),
      "taxesWithheld" => array(),
      "generalDiscounts" => array(),
      "generalCharges" => array(),
      "invoiceAmount" => 0,
      "grossAmount" => 0,
      "totalGeneralDiscounts" => 0,
      "totalGeneralCharges" => 0,
      "totalTaxesOutputs" => 0,
      "totalTaxesWithheld" => 0
    );

    // Precalculate total global amount (needed for general discounts and charges)
    $items = [];
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData($this);
      $totals['grossAmount'] += $item['grossAmount'];
      $items[] = $item;
    }

    // Get general discounts and charges
    foreach (['discounts', 'charges'] as $groupTag) {
      foreach ($this->{$groupTag} as $item) {
        if ($item['rate'] === null) {
          $rate = null;
          $amount = $item['amount'];
        } else {
          $rate = $item['rate'];
          $amount = $totals['grossAmount'] * ($rate / 100);
        }
        $totals['general' . ucfirst($groupTag)][] = [
          "reason" => $item['reason'],
          "rate" => $rate,
          "amount" => $amount
        ];
        $totals['totalGeneral' . ucfirst($groupTag)] += $amount;
      }
    }
    $effectiveGeneralCharge = $totals['totalGeneralCharges'] - $totals['totalGeneralDiscounts'];

    // Run through every item
    foreach ($items as &$item) {
      $totals['totalTaxesOutputs'] += $item['totalTaxesOutputs'];
      $totals['totalTaxesWithheld'] += $item['totalTaxesWithheld'];

      // Get taxes
      foreach (["taxesOutputs", "taxesWithheld"] as $taxGroup) {
        foreach ($item[$taxGroup] as $type=>$tax) {
          if (!isset($totals[$taxGroup][$type])) {
            $totals[$taxGroup][$type] = array();
          }
          $taxKey = $tax['rate'] . ":" . $tax['surcharge'];
          if (!isset($totals[$taxGroup][$type][$taxKey])) {
            $totals[$taxGroup][$type][$taxKey] = array(
              "base" => 0,
              "rate" => $tax['rate'],
              "surcharge" => $tax['surcharge'],
              "amount" => 0,
              "surchargeAmount" => 0
            );
          }
          $totals[$taxGroup][$type][$taxKey]['base'] += $tax['base'];
          $totals[$taxGroup][$type][$taxKey]['amount'] += $tax['amount'];
          $totals[$taxGroup][$type][$taxKey]['surchargeAmount'] += $tax['surchargeAmount'];
        }
      }
    }

    // Apply effective general discounts and charges to total taxes
    if ($effectiveGeneralCharge != 0) {
      foreach (["taxesOutputs", "taxesWithheld"] as $taxGroup) {
        $totals['total' . ucfirst($taxGroup)] = 0; // Reset total (needs to be recalculated)
        foreach ($totals[$taxGroup] as $type=>&$taxes) {
          foreach ($taxes as &$tax) {
            $proportion = $tax['base'] / $totals['grossAmount'];
            $tax['base'] += $effectiveGeneralCharge * $proportion;
            $tax['amount'] = $tax['base'] * ($tax['rate'] / 100);
            $tax['surchargeAmount'] = $tax['base'] * ($tax['surcharge'] / 100);
            $totals['total' . ucfirst($taxGroup)] += $tax['amount'] + $tax['surchargeAmount'];
          }
        }
      }
    }

    // Pre-round some total values (needed to create a sum-reasonable invoice total)
    $totals['totalTaxesOutputs'] = $this->pad($totals['totalTaxesOutputs'], 'TotalTaxOutputs');
    $totals['totalTaxesWithheld'] = $this->pad($totals['totalTaxesWithheld'], 'TotalTaxesWithheld');
    $totals['totalGeneralDiscounts'] = $this->pad($totals['totalGeneralDiscounts'], 'TotalGeneralDiscounts');
    $totals['totalGeneralCharges'] = $this->pad($totals['totalGeneralCharges'], 'TotalGeneralSurcharges');
    $totals['grossAmount'] = $this->pad($totals['grossAmount'], 'TotalGrossAmount');

    // Fill missing values
    $totals['grossAmountBeforeTaxes'] = $this->pad(
      $totals['grossAmount'] - $totals['totalGeneralDiscounts'] + $totals['totalGeneralCharges'],
      'TotalGrossAmountBeforeTaxes'
    );
    $totals['invoiceAmount'] = $totals['grossAmountBeforeTaxes'] + $totals['totalTaxesOutputs'] - $totals['totalTaxesWithheld'];

    return $totals;
  }

}
