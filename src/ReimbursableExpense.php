<?php
namespace josemmo\Facturae;

class ReimbursableExpense {
  /**
   * Seller
   * @var FacturaeParty|null
   */
  public $seller = null;

  /**
   * Buyer
   * @var FacturaeParty|null
   */
  public $buyer = null;

  /**
   * Issue date (as UNIX timestamp or parseable date string)
   * @var string|int|null
   */
  public $issueDate = null;

  /**
   * Invoice number
   * @var string|null
   */
  public $invoiceNumber = null;

  /**
   * Invoice series code
   * @var string|null
   */
  public $invoiceSeriesCode = null;

  /**
   * Amount
   * @var int|float
   */
  public $amount = 0;

  /**
   * Class constructor
   *
   * @param array $properties Reimbursable expense properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) {
      $this->{$key} = $value;
    }
  }
}
