<?php
namespace josemmo\Facturae;

/**
 * Facturae Payment
 *
 * Represents the payment details for an invoice.
 */
class FacturaePayment {
  const TYPE_CASH = "01";
  const TYPE_DEBIT = "02";
  const TYPE_RECEIPT = "03";
  const TYPE_TRANSFER = "04";
  const TYPE_ACCEPTED_BILL_OF_EXCHANGE = "05";
  const TYPE_DOCUMENTARY_CREDIT = "06";
  const TYPE_CONTRACT_AWARD = "07";
  const TYPE_BILL_OF_EXCHANGE = "08";
  const TYPE_TRANSFERABLE_IOU = "09";
  const TYPE_IOU = "10";
  const TYPE_CHEQUE = "11";
  const TYPE_REIMBURSEMENT = "12";
  const TYPE_SPECIAL = "13";
  const TYPE_SETOFF = "14";
  const TYPE_POSTGIRO = "15";
  const TYPE_CERTIFIED_CHEQUE = "16";
  const TYPE_BANKERS_DRAFT = "17";
  const TYPE_CASH_ON_DELIVERY = "18";
  const TYPE_CARD = "19";

  /**
   * Payment method code
   * @var string
   */
  public $method = self::TYPE_CASH;

  /**
   * Payment due date (as UNIX timestamp or parsable date string)
   * @var int|string|null
   */
  public $dueDate = null;

  /**
   * Payment installment amount
   * @var float|null
   */
  public $amount = null;

  /**
   * Bank account number (IBAN)
   * @var string|null
   */
  public $iban = null;

  /**
   * SWIFT/BIC code of bank account
   * @var string|null
   */
  public $bic = null;

  /**
   * Class constructor
   *
   * @param array $properties Payment properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) $this->{$key} = $value;
  }
}
