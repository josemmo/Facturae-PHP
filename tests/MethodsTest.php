<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use PHPUnit\Framework\TestCase;

final class MethodsTest extends TestCase {

  /**
   * Test methods
   */
  public function testMethods() {
    // Schema
    $schema = Facturae::SCHEMA_3_2_2;
    $fac = new Facturae($schema);
    $this->assertEquals($schema, $fac->getSchemaVersion());

    // Parties
    $seller = new FacturaeParty(['name'=>'Seller']);
    $buyer = new FacturaeParty(['name'=>'Buyer']);
    $fac->setSeller($seller)->setBuyer($buyer);
    $this->assertEquals($seller, $fac->getSeller());
    $this->assertEquals($buyer, $fac->getBuyer());

    // Check number
    $number = array(
      "serie" => "AAAA",
      "number" => 123
    );
    $fac->setNumber($number['serie'], $number['number']);
    $this->assertEquals($number, $fac->getNumber());

    // Dates
    $issueDate = strtotime('2019-01-01');
    $dueDate = strtotime('2019-01-22');
    $billPeriod = array(
      "startDate" => strtotime('2018-12-01'),
      "endDate" => strtotime('2018-12-31')
    );
    $fac->setDates($issueDate, $dueDate);
    $fac->setBillingPeriod($billPeriod['startDate'], $billPeriod['endDate']);
    $this->assertEquals($issueDate, $fac->getIssueDate());
    $this->assertEquals($dueDate, $fac->getDueDate());
    $this->assertEquals($billPeriod, $fac->getBillingPeriod());

    // Payment method
    $paymentMethod = Facturae::PAYMENT_CASH;
    $fac->setPaymentMethod($paymentMethod);
    $this->assertEquals($paymentMethod, $fac->getPaymentMethod());
    $this->assertEquals(null, $fac->getPaymentIBAN());

    // Description and references
    $description = "This is a test description";
    $fileRef = "File";
    $txRef = "Transaction";
    $contractRef = "Contract";
    $fac->setDescription($description);
    $fac->setReferences($fileRef, $txRef, $contractRef);
    $this->assertEquals($description, $fac->getDescription());
    $this->assertEquals($fileRef, $fac->getFileReference());
    $this->assertEquals($txRef, $fac->getTransactionReference());
    $this->assertEquals($contractRef, $fac->getContractReference());

    // Legal literals
    $literals = ['First', 'Second', 'Third'];
    foreach ($literals as $literal) $fac->addLegalLiteral($literal);
    $this->assertEquals($literals, $fac->getLegalLiterals());
    $fac->clearLegalLiterals();
    $this->assertEquals([], $fac->getLegalLiterals());

    // Discounts and charges
    $fac->addDiscount('First', 10);
    $fac->addDiscount('Second', 15, false);
    $fac->addCharge('First', 20);
    $fac->addCharge('Second', 25, false);
    $fac->addCharge('Third', 30);
    $this->assertEquals(2, count($fac->getDiscounts()));
    $this->assertEquals(3, count($fac->getCharges()));
    $fac->clearDiscounts();
    $this->assertEquals([], $fac->getDiscounts());
    $fac->clearCharges();
    $this->assertEquals([], $fac->getCharges());

    // Items
    $items = array(
      new FacturaeItem(['name'=>'First item']),
      new FacturaeItem(['name'=>'Second item']),
      new FacturaeItem(['name'=>'Third item'])
    );
    foreach ($items as $item) $fac->addItem($item);
    $this->assertEquals($items, $fac->getItems());
    $fac->clearItems();
    $this->assertEquals([], $fac->getItems());
  }

}
