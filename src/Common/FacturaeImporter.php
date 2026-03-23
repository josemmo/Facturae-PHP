<?php
namespace josemmo\Facturae\Common;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use josemmo\Facturae\CorrectiveDetails;
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeCentre;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaePayment;
use josemmo\Facturae\ReimbursableExpense;

/**
 * Imports Facturae XML documents into {@see Facturae} instances.
 */
class FacturaeImporter {
  /** @var DOMXPath */
  private $xpath;


  /**
   * Load invoice from XML content.
   *
   * @param  string   $xmlContent XML content
   * @return Facturae             Hydrated invoice instance
   */
  public function loadXml(string $xmlContent): Facturae {
    $dom = new DOMDocument();
    $loaded = @$dom->loadXML($xmlContent, LIBXML_NONET);
    if (!$loaded || $dom->documentElement === null) {
      throw new InvalidArgumentException('Invalid Facturae XML content');
    }

    // Export produces <fe:Facturae xmlns:fe="..."> at the root, but ALL child
    // elements (FileHeader, Parties, etc.) are written WITHOUT a namespace
    // prefix and therefore belong to NO namespace. XPath queries for children
    // must NOT use any prefix; only the document root uses the fe: prefix.
    $root = $dom->documentElement;
    $this->xpath = new DOMXPath($dom);

    $version = $this->getNodeValue($this->queryOne('./FileHeader/SchemaVersion', $root));
    if ($version === null || $version === '') {
      $version = Facturae::SCHEMA_3_2_1;
    }
    $invoice = new Facturae($version);

    $fileHeaderNode = $this->queryOne('./FileHeader', $root);
    if ($fileHeaderNode !== null) {
      $this->hydrate($invoice, $fileHeaderNode);
    }

    $factoringNode = $this->queryOne('./FactoringAssignmentData', $root);
    if ($factoringNode !== null) {
      $this->hydrateFactoringAssignmentData($invoice, $factoringNode);
    }

    $partiesNode = $this->queryOne('./Parties', $root);
    if ($partiesNode !== null) {
      $this->hydrate($invoice, $partiesNode);
    }

    $invoiceNode = $this->queryOne('./Invoices/Invoice[1]', $root);
    if ($invoiceNode !== null) {
      $this->hydrate($invoice, $invoiceNode);
    }

    return $invoice;
  }


  /**
   * Hydrate an object from an XML node.
   *
   * @param object     $target Target object to hydrate
   * @param DOMElement $node   XML node
   */
  private function hydrate(object $target, DOMElement $node): void {
    $processed = [];

    foreach ($this->getChildElements($node) as $child) {
      $name = $child->localName;
      if (isset($processed[$name])) {
        continue;
      }

      if (
        $name === 'InvoiceNumber' &&
        method_exists($target, 'setNumber') &&
        $target instanceof Facturae
      ) {
        $number = trim($child->textContent);
        $series = $this->getNodeValue($this->getFirstChildByName($node, 'InvoiceSeriesCode'));
        $target->setNumber($series, $number);
        $processed['InvoiceNumber'] = true;
        $processed['InvoiceSeriesCode'] = true;
        continue;
      }

      $partySetterMap = [
        'SellerParty' => 'setSeller',
        'BuyerParty'  => 'setBuyer',
        'ThirdParty'  => 'setThirdParty',
        'Assignee'    => 'setAssignee',
      ];
      if ($target instanceof Facturae && isset($partySetterMap[$name])) {
        $target->{$partySetterMap[$name]}($this->createPartyFromNode($child));
        continue;
      }

      if ($target instanceof Facturae && $name === 'Items') {
        foreach ($this->queryAll('./f:InvoiceLine', $child) as $lineNode) {
          $target->addItem($this->createItemFromNode($lineNode));
        }
        continue;
      }

      if ($target instanceof Facturae && $name === 'PaymentDetails') {
        foreach ($this->queryAll('./f:Installment', $child) as $installment) {
          $target->addPayment($this->createPaymentFromNode($installment));
        }
        continue;
      }

      if ($target instanceof Facturae && $name === 'LegalLiterals') {
        foreach ($this->queryAll('./f:LegalReference', $child) as $reference) {
          $target->addLegalLiteral(trim($reference->textContent));
        }
        continue;
      }

      if ($target instanceof Facturae && $name === 'InvoiceTotals') {
        $this->hydrateInvoiceTotals($target, $child);
        continue;
      }

      if ($target instanceof Facturae && $name === 'AdditionalData') {
        $this->hydrateAdditionalData($target, $child);
        continue;
      }

      if ($target instanceof Facturae && $name === 'FactoringAssignmentData') {
        $this->hydrateFactoringAssignmentData($target, $child);
        continue;
      }

      if ($target instanceof Facturae && $name === 'InvoiceHeader') {
        $this->hydrateInvoiceHeader($target, $child);
        continue;
      }

      if ($target instanceof Facturae && $name === 'InvoiceIssueData') {
        $this->hydrateInvoiceIssueData($target, $child);
        continue;
      }

      if ($target instanceof Facturae && $name === 'Parties') {
        $this->hydrate($target, $child);
        continue;
      }

      if (!$this->hasElementChildren($child)) {
        $setter = 'set' . $name;
        if (method_exists($target, $setter)) {
          $target->{$setter}(trim($child->textContent));
        }
      }
    }
  }


  /**
   * Hydrate invoice header block.
   *
   * @param Facturae   $invoice Invoice
   * @param DOMElement $node    InvoiceHeader node
   */
  private function hydrateInvoiceHeader(Facturae $invoice, DOMElement $node): void {
    $this->hydrate($invoice, $node);

    $type = $this->getNodeValue($this->queryOne('./f:InvoiceDocumentType', $node));
    if ($type !== null) {
      $invoice->setType($type);
    }

    $correctiveNode = $this->queryOne('./f:Corrective', $node);
    if ($correctiveNode !== null) {
      $invoice->setCorrective($this->createCorrectiveFromNode($correctiveNode));
    }
  }


  /**
   * Hydrate invoice issue data block.
   *
   * @param Facturae   $invoice Invoice
   * @param DOMElement $node    InvoiceIssueData node
   */
  private function hydrateInvoiceIssueData(Facturae $invoice, DOMElement $node): void {
    $this->hydrate($invoice, $node);

    $description = $this->getNodeValue($this->queryOne('./f:InvoiceDescription', $node));
    if ($description !== null) {
      $invoice->setDescription($description);
    }

    $fileReference = $this->getNodeValue($this->queryOne('./f:FileReference', $node));
    $transactionReference = $this->getNodeValue($this->queryOne('./f:ReceiverTransactionReference', $node));
    $contractReference = $this->getNodeValue($this->queryOne('./f:ReceiverContractReference', $node));
    if ($fileReference !== null || $transactionReference !== null || $contractReference !== null) {
      $invoice->setReferences($fileReference, $transactionReference, $contractReference);
    }

    $periodNode = $this->queryOne('./f:InvoicingPeriod', $node);
    if ($periodNode !== null) {
      $startDate = $this->getNodeValue($this->queryOne('./f:StartDate', $periodNode));
      $endDate = $this->getNodeValue($this->queryOne('./f:EndDate', $periodNode));
      if ($startDate !== null && $endDate !== null) {
        $invoice->setBillingPeriod($startDate, $endDate);
      }
    }
  }


  /**
   * Hydrate invoice totals block.
   *
   * @param Facturae   $invoice Invoice
   * @param DOMElement $node    InvoiceTotals node
   */
  private function hydrateInvoiceTotals(Facturae $invoice, DOMElement $node): void {
    foreach ($this->queryAll('./f:GeneralDiscounts/f:Discount', $node) as $discountNode) {
      $reason = $this->getNodeValue($this->queryOne('./f:DiscountReason', $discountNode));
      $rate = $this->getNodeValue($this->queryOne('./f:DiscountRate', $discountNode));
      $amount = $this->getNodeValue($this->queryOne('./f:DiscountAmount', $discountNode));
      if ($reason === null) {
        continue;
      }
      if ($rate !== null) {
        $invoice->addDiscount($reason, (float) $rate, true);
      } elseif ($amount !== null) {
        $invoice->addDiscount($reason, (float) $amount, false);
      }
    }

    foreach ($this->queryAll('./f:GeneralSurcharges/f:Charge', $node) as $chargeNode) {
      $reason = $this->getNodeValue($this->queryOne('./f:ChargeReason', $chargeNode));
      $rate = $this->getNodeValue($this->queryOne('./f:ChargeRate', $chargeNode));
      $amount = $this->getNodeValue($this->queryOne('./f:ChargeAmount', $chargeNode));
      if ($reason === null) {
        continue;
      }
      if ($rate !== null) {
        $invoice->addCharge($reason, (float) $rate, true);
      } elseif ($amount !== null) {
        $invoice->addCharge($reason, (float) $amount, false);
      }
    }

    foreach ($this->queryAll('./f:ReimbursableExpenses/f:ReimbursableExpenses', $node) as $expenseNode) {
      $invoice->addReimbursableExpense($this->createReimbursableExpenseFromNode($expenseNode));
    }
  }


  /**
   * Hydrate additional data block.
   *
   * @param Facturae   $invoice Invoice
   * @param DOMElement $node    AdditionalData node
   */
  private function hydrateAdditionalData(Facturae $invoice, DOMElement $node): void {
    $relatedInvoice = $this->getNodeValue($this->queryOne('./f:RelatedInvoice', $node));
    if ($relatedInvoice !== null) {
      $invoice->setRelatedInvoice($relatedInvoice);
    }

    $additionalInformation = $this->getNodeValue($this->queryOne('./f:InvoiceAdditionalInformation', $node));
    if ($additionalInformation !== null) {
      $invoice->setAdditionalInformation($additionalInformation);
    }

    foreach ($this->queryAll('./f:RelatedDocuments/f:Attachment', $node) as $attachmentNode) {
      $format = $this->getNodeValue($this->queryOne('./f:AttachmentFormat', $attachmentNode));
      $description = $this->getNodeValue($this->queryOne('./f:AttachmentDescription', $attachmentNode));
      $data = $this->getNodeValue($this->queryOne('./f:AttachmentData', $attachmentNode));
      if ($data === null) {
        continue;
      }

      $file = new FacturaeFile();
      $extension = empty($format) ? 'bin' : strtolower($format);
      $file->loadData(base64_decode($data), 'attachment.' . $extension);
      $invoice->addAttachment($file, $description);
    }
  }


  /**
   * Hydrate factoring assignment data.
   *
   * @param Facturae   $invoice Invoice
   * @param DOMElement $node    FactoringAssignmentData node
   */
  private function hydrateFactoringAssignmentData(Facturae $invoice, DOMElement $node): void {
    $assigneeNode = $this->queryOne('./Assignee', $node);
    if ($assigneeNode !== null) {
      $invoice->setAssignee($this->createPartyFromNode($assigneeNode));
    }

    $clauses = $this->getNodeValue($this->queryOne('./FactoringAssignmentClauses', $node));
    if ($clauses !== null) {
      $invoice->setAssignmentClauses($clauses);
    }
    // NOTE: PaymentDetails in FactoringAssignmentData is identical to the
    // PaymentDetails inside Invoice. Payments are captured from the Invoice
    // block by hydrate() to avoid counting them twice.
  }


  /**
   * Create party object from XML node.
   *
   * @param  DOMElement   $node Party node
   * @return FacturaeParty       Party object
   */
  private function createPartyFromNode(DOMElement $node): FacturaeParty {
    $properties = [];

    $personTypeCode = $this->getNodeValue($this->queryOne('./f:TaxIdentification/f:PersonTypeCode', $node));
    if ($personTypeCode !== null) {
      $properties['isLegalEntity'] = ($personTypeCode === 'J');
    }

    $taxNumber = $this->getNodeValue($this->queryOne('./f:TaxIdentification/f:TaxIdentificationNumber', $node));
    if ($taxNumber !== null) {
      $properties['taxNumber'] = $taxNumber;
    }

    $legalNode = $this->queryOne('./f:LegalEntity', $node);
    if ($legalNode !== null) {
      $properties['name'] = $this->getNodeValue($this->queryOne('./f:CorporateName', $legalNode));
      $properties['tradeName'] = $this->getNodeValue($this->queryOne('./f:TradeName', $legalNode));

      $registrationNode = $this->queryOne('./f:RegistrationData', $legalNode);
      if ($registrationNode !== null) {
        $registrationMap = [
          'Book' => 'book',
          'RegisterOfCompaniesLocation' => 'registerOfCompaniesLocation',
          'Sheet' => 'sheet',
          'Folio' => 'folio',
          'Section' => 'section',
          'Volume' => 'volume',
          'AdditionalRegistrationData' => 'additionalRegistrationData',
        ];
        foreach ($registrationMap as $xmlName => $propertyName) {
          $value = $this->getNodeValue($this->queryOne('./f:' . $xmlName, $registrationNode));
          if ($value !== null) {
            $properties[$propertyName] = $value;
          }
        }
      }
    }

    $individualNode = $this->queryOne('./f:Individual', $node);
    if ($individualNode !== null) {
      $properties['isLegalEntity'] = false;
      $properties['name'] = $this->getNodeValue($this->queryOne('./f:Name', $individualNode));
      $properties['firstSurname'] = $this->getNodeValue($this->queryOne('./f:FirstSurname', $individualNode));
      $properties['lastSurname'] = $this->getNodeValue($this->queryOne('./f:SecondSurname', $individualNode));
    }

    $addressInSpain = $this->queryOne('./f:LegalEntity/f:AddressInSpain|./f:Individual/f:AddressInSpain|./f:AddressInSpain', $node);
    if ($addressInSpain !== null) {
      $properties['address'] = $this->getNodeValue($this->queryOne('./f:Address', $addressInSpain));
      $properties['postCode'] = $this->getNodeValue($this->queryOne('./f:PostCode', $addressInSpain));
      $properties['town'] = $this->getNodeValue($this->queryOne('./f:Town', $addressInSpain));
      $properties['province'] = $this->getNodeValue($this->queryOne('./f:Province', $addressInSpain));
      $properties['countryCode'] = $this->getNodeValue($this->queryOne('./f:CountryCode', $addressInSpain));
    }

    $overseasAddress = $this->queryOne('./f:LegalEntity/f:OverseasAddress|./f:Individual/f:OverseasAddress|./f:OverseasAddress', $node);
    if ($overseasAddress !== null) {
      $properties['address'] = $this->getNodeValue($this->queryOne('./f:Address', $overseasAddress));
      $properties['province'] = $this->getNodeValue($this->queryOne('./f:Province', $overseasAddress));
      $properties['countryCode'] = $this->getNodeValue($this->queryOne('./f:CountryCode', $overseasAddress));

      $postCodeAndTown = $this->getNodeValue($this->queryOne('./f:PostCodeAndTown', $overseasAddress));
      if ($postCodeAndTown !== null) {
        $parts = preg_split('/\s+/', trim($postCodeAndTown), 2);
        $properties['postCode'] = $parts[0] ?? null;
        $properties['town'] = $parts[1] ?? null;
      }
    }

    $contactNode = $this->queryOne('./f:LegalEntity/f:ContactDetails|./f:Individual/f:ContactDetails|./f:ContactDetails', $node);
    if ($contactNode !== null) {
      $contactMap = [
        'Telephone' => 'phone',
        'TeleFax' => 'fax',
        'WebAddress' => 'website',
        'ElectronicMail' => 'email',
        'ContactPersons' => 'contactPeople',
        'CnoCnae' => 'cnoCnae',
        'INETownCode' => 'ineTownCode',
      ];
      foreach ($contactMap as $xmlName => $propertyName) {
        $value = $this->getNodeValue($this->queryOne('./f:' . $xmlName, $contactNode));
        if ($value !== null) {
          $properties[$propertyName] = $value;
        }
      }
    }

    $centres = [];
    foreach ($this->queryAll('./f:AdministrativeCentres/f:AdministrativeCentre', $node) as $centreNode) {
      $centres[] = $this->createCentreFromNode($centreNode);
    }
    if (!empty($centres)) {
      $properties['centres'] = $centres;
    }

    return new FacturaeParty(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create administrative centre object from XML node.
   *
   * @param  DOMElement    $node AdministrativeCentre node
   * @return FacturaeCentre      Administrative centre object
   */
  private function createCentreFromNode(DOMElement $node): FacturaeCentre {
    $properties = [
      'code' => $this->getNodeValue($this->queryOne('./f:CentreCode', $node)),
      'role' => $this->getNodeValue($this->queryOne('./f:RoleTypeCode', $node)),
      'name' => $this->getNodeValue($this->queryOne('./f:Name', $node)),
      'firstSurname' => $this->getNodeValue($this->queryOne('./f:FirstSurname', $node)),
      'lastSurname' => $this->getNodeValue($this->queryOne('./f:SecondSurname', $node)),
      'description' => $this->getNodeValue($this->queryOne('./f:CentreDescription', $node)),
    ];

    $addressInSpain = $this->queryOne('./f:AddressInSpain', $node);
    if ($addressInSpain !== null) {
      $properties['address'] = $this->getNodeValue($this->queryOne('./f:Address', $addressInSpain));
      $properties['postCode'] = $this->getNodeValue($this->queryOne('./f:PostCode', $addressInSpain));
      $properties['town'] = $this->getNodeValue($this->queryOne('./f:Town', $addressInSpain));
      $properties['province'] = $this->getNodeValue($this->queryOne('./f:Province', $addressInSpain));
      $properties['countryCode'] = $this->getNodeValue($this->queryOne('./f:CountryCode', $addressInSpain));
    }

    $overseasAddress = $this->queryOne('./f:OverseasAddress', $node);
    if ($overseasAddress !== null) {
      $properties['address'] = $this->getNodeValue($this->queryOne('./f:Address', $overseasAddress));
      $properties['province'] = $this->getNodeValue($this->queryOne('./f:Province', $overseasAddress));
      $properties['countryCode'] = $this->getNodeValue($this->queryOne('./f:CountryCode', $overseasAddress));

      $postCodeAndTown = $this->getNodeValue($this->queryOne('./f:PostCodeAndTown', $overseasAddress));
      if ($postCodeAndTown !== null) {
        $parts = preg_split('/\s+/', trim($postCodeAndTown), 2);
        $properties['postCode'] = $parts[0] ?? null;
        $properties['town'] = $parts[1] ?? null;
      }
    }

    return new FacturaeCentre(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create invoice item from XML node.
   *
   * @param  DOMElement  $node InvoiceLine node
   * @return FacturaeItem       Item object
   */
  private function createItemFromNode(DOMElement $node): FacturaeItem {
    $properties = [
      'name' => $this->getNodeValue($this->queryOne('./f:ItemDescription', $node)),
      'description' => $this->getNodeValue($this->queryOne('./f:AdditionalLineItemInformation', $node)),
      'articleCode' => $this->getNodeValue($this->queryOne('./f:ArticleCode', $node)),
      'quantity' => (float) ($this->getNodeValue($this->queryOne('./f:Quantity', $node)) ?? 1),
      'unitOfMeasure' => $this->getNodeValue($this->queryOne('./f:UnitOfMeasure', $node)),
      'unitPriceWithoutTax' => (float) ($this->getNodeValue($this->queryOne('./f:UnitPriceWithoutTax', $node)) ?? 0),
    ];

    $optionalNames = [
      'IssuerContractReference', 'IssuerContractDate',
      'IssuerTransactionReference', 'IssuerTransactionDate',
      'ReceiverContractReference', 'ReceiverContractDate',
      'ReceiverTransactionReference', 'ReceiverTransactionDate',
      'FileReference', 'FileDate', 'SequenceNumber',
    ];
    foreach ($optionalNames as $xmlName) {
      $value = $this->getNodeValue($this->queryOne('./f:' . $xmlName, $node));
      if ($value !== null) {
        $properties[lcfirst($xmlName)] = $value;
      }
    }

    $linePeriod = $this->queryOne('./f:LineItemPeriod', $node);
    if ($linePeriod !== null) {
      $properties['periodStart'] = $this->getNodeValue($this->queryOne('./f:StartDate', $linePeriod));
      $properties['periodEnd'] = $this->getNodeValue($this->queryOne('./f:EndDate', $linePeriod));
    }

    $specialTaxableEvent = $this->queryOne('./f:SpecialTaxableEvent', $node);
    if ($specialTaxableEvent !== null) {
      $properties['specialTaxableEventCode'] = $this->getNodeValue($this->queryOne('./f:SpecialTaxableEventCode', $specialTaxableEvent));
      $properties['specialTaxableEventReason'] = $this->getNodeValue($this->queryOne('./f:SpecialTaxableEventReason', $specialTaxableEvent));
    }

    $discounts = [];
    foreach ($this->queryAll('./f:DiscountsAndRebates/f:Discount', $node) as $discountNode) {
      $entry = [
        'reason' => $this->getNodeValue($this->queryOne('./f:DiscountReason', $discountNode)),
      ];
      $rate = $this->getNodeValue($this->queryOne('./f:DiscountRate', $discountNode));
      $amount = $this->getNodeValue($this->queryOne('./f:DiscountAmount', $discountNode));
      if ($rate !== null) {
        $entry['rate'] = (float) $rate;
      } elseif ($amount !== null) {
        $entry['amount'] = (float) $amount;
      }
      $discounts[] = $entry;
    }
    if (!empty($discounts)) {
      $properties['discounts'] = $discounts;
    }

    $charges = [];
    foreach ($this->queryAll('./f:Charges/f:Charge', $node) as $chargeNode) {
      $entry = [
        'reason' => $this->getNodeValue($this->queryOne('./f:ChargeReason', $chargeNode)),
      ];
      $rate = $this->getNodeValue($this->queryOne('./f:ChargeRate', $chargeNode));
      $amount = $this->getNodeValue($this->queryOne('./f:ChargeAmount', $chargeNode));
      if ($rate !== null) {
        $entry['rate'] = (float) $rate;
      } elseif ($amount !== null) {
        $entry['amount'] = (float) $amount;
      }
      $charges[] = $entry;
    }
    if (!empty($charges)) {
      $properties['charges'] = $charges;
    }

    $taxes = [];
    foreach (['TaxesOutputs' => false, 'TaxesWithheld' => true] as $groupName => $isWithheld) {
      foreach ($this->queryAll('./f:' . $groupName . '/f:Tax', $node) as $taxNode) {
        $type = $this->getNodeValue($this->queryOne('./f:TaxTypeCode', $taxNode));
        if ($type === null) {
          continue;
        }

        $taxes[$type] = [
          'rate' => (float) ($this->getNodeValue($this->queryOne('./f:TaxRate', $taxNode)) ?? 0),
          'surcharge' => (float) ($this->getNodeValue($this->queryOne('./f:EquivalenceSurcharge', $taxNode)) ?? 0),
          'isWithheld' => $isWithheld,
        ];
      }
    }
    if (!empty($taxes)) {
      $properties['taxes'] = $taxes;
    }

    return new FacturaeItem(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create payment object from XML installment node.
   *
   * @param  DOMElement      $node Installment node
   * @return FacturaePayment       Payment object
   */
  private function createPaymentFromNode(DOMElement $node): FacturaePayment {
    $properties = [
      'dueDate' => $this->getNodeValue($this->queryOne('./f:InstallmentDueDate', $node)),
      'amount' => (float) ($this->getNodeValue($this->queryOne('./f:InstallmentAmount', $node)) ?? 0),
      'method' => $this->getNodeValue($this->queryOne('./f:PaymentMeans', $node)),
    ];

    $iban = $this->getNodeValue($this->queryOne('./f:AccountToBeCredited/f:IBAN|./f:AccountToBeDebited/f:IBAN', $node));
    $bic = $this->getNodeValue($this->queryOne('./f:AccountToBeCredited/f:BIC|./f:AccountToBeDebited/f:BIC', $node));
    if ($iban !== null) {
      $properties['iban'] = $iban;
    }
    if ($bic !== null) {
      $properties['bic'] = $bic;
    }

    return new FacturaePayment(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create corrective details object from XML node.
   *
   * @param  DOMElement        $node Corrective node
   * @return CorrectiveDetails       Corrective details object
   */
  private function createCorrectiveFromNode(DOMElement $node): CorrectiveDetails {
    $properties = [
      'invoiceNumber' => $this->getNodeValue($this->queryOne('./f:InvoiceNumber', $node)),
      'invoiceSeriesCode' => $this->getNodeValue($this->queryOne('./f:InvoiceSeriesCode', $node)),
      'reason' => $this->getNodeValue($this->queryOne('./f:ReasonCode', $node)),
      'reasonDescription' => $this->getNodeValue($this->queryOne('./f:ReasonDescription', $node)),
      'correctionMethod' => $this->getNodeValue($this->queryOne('./f:CorrectionMethod', $node)),
      'correctionMethodDescription' => $this->getNodeValue($this->queryOne('./f:CorrectionMethodDescription', $node)),
      'additionalReasonDescription' => $this->getNodeValue($this->queryOne('./f:AdditionalReasonDescription', $node)),
      'invoiceIssueDate' => $this->getNodeValue($this->queryOne('./f:InvoiceIssueDate', $node)),
    ];

    $taxPeriodNode = $this->queryOne('./f:TaxPeriod', $node);
    if ($taxPeriodNode !== null) {
      $properties['taxPeriodStart'] = $this->getNodeValue($this->queryOne('./f:StartDate', $taxPeriodNode));
      $properties['taxPeriodEnd'] = $this->getNodeValue($this->queryOne('./f:EndDate', $taxPeriodNode));
    }

    return new CorrectiveDetails(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create reimbursable expense object from XML node.
   *
   * @param  DOMElement          $node Reimbursable expense node
   * @return ReimbursableExpense       Reimbursable expense object
   */
  private function createReimbursableExpenseFromNode(DOMElement $node): ReimbursableExpense {
    $properties = [
      'issueDate' => $this->getNodeValue($this->queryOne('./f:IssueDate', $node)),
      'invoiceNumber' => $this->getNodeValue($this->queryOne('./f:InvoiceNumber', $node)),
      'invoiceSeriesCode' => $this->getNodeValue($this->queryOne('./f:InvoiceSeriesCode', $node)),
      'amount' => (float) ($this->getNodeValue($this->queryOne('./f:ReimbursableExpensesAmount', $node)) ?? 0),
    ];

    $sellerNode = $this->queryOne('./f:ReimbursableExpensesSellerParty', $node);
    if ($sellerNode !== null) {
      $properties['seller'] = $this->createReimbursablePartyFromNode($sellerNode);
    }

    $buyerNode = $this->queryOne('./f:ReimbursableExpensesBuyerParty', $node);
    if ($buyerNode !== null) {
      $properties['buyer'] = $this->createReimbursablePartyFromNode($buyerNode);
    }

    return new ReimbursableExpense(array_filter($properties, static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Create party for reimbursable expense blocks.
   *
   * @param  DOMElement   $node Party node
   * @return FacturaeParty       Party object
   */
  private function createReimbursablePartyFromNode(DOMElement $node): FacturaeParty {
    return new FacturaeParty(array_filter([
      'isLegalEntity' => $this->getNodeValue($this->queryOne('./f:PersonTypeCode', $node)) === 'J',
      'taxNumber' => $this->getNodeValue($this->queryOne('./f:TaxIdentificationNumber', $node)),
    ], static function ($value) {
      return $value !== null;
    }));
  }


  /**
   * Query first node matching XPath expression.
   *
   * @param  string           $expression XPath expression
   * @param  DOMElement|null  $context    Context node
   * @return DOMElement|null              First node or null
   */
  private function queryOne(string $expression, ?DOMElement $context=null): ?DOMElement {
    // Strip the internal 'f:' alias used for readability; child elements of
    // fe:Facturae carry no namespace prefix in the exported XML.
    $expression = str_replace('f:', '', $expression);
    $nodes = $this->xpath->query($expression, $context);
    if ($nodes === false || $nodes->length === 0) {
      return null;
    }
    $node = $nodes->item(0);
    return $node instanceof DOMElement ? $node : null;
  }


  /**
   * Query all nodes matching XPath expression.
   *
   * @param  string          $expression XPath expression
   * @param  DOMElement|null $context    Context node
   * @return DOMElement[]                Matching nodes
   */
  private function queryAll(string $expression, ?DOMElement $context=null): array {
    // Strip the internal 'f:' alias used for readability.
    $expression = str_replace('f:', '', $expression);
    $nodes = $this->xpath->query($expression, $context);
    if ($nodes === false || $nodes->length === 0) {
      return [];
    }

    $result = [];
    foreach ($nodes as $node) {
      if ($node instanceof DOMElement) {
        $result[] = $node;
      }
    }
    return $result;
  }


  /**
   * Get value of a node.
   *
   * @param  DOMElement|null $node XML node
   * @return string|null           Node value
   */
  private function getNodeValue(?DOMElement $node): ?string {
    if ($node === null) {
      return null;
    }
    $value = trim($node->textContent);
    return ($value === '') ? null : $value;
  }


  /**
   * Get first direct child with given local name.
   *
   * @param  DOMElement      $node      Parent node
   * @param  string          $childName Child local name
   * @return DOMElement|null            Child node or null
   */
  private function getFirstChildByName(DOMElement $node, string $childName): ?DOMElement {
    foreach ($this->getChildElements($node) as $child) {
      if ($child->localName === $childName) {
        return $child;
      }
    }
    return null;
  }


  /**
   * Get direct child elements of a node.
   *
   * @param  DOMElement   $node Parent node
   * @return DOMElement[]       Child elements
   */
  private function getChildElements(DOMElement $node): array {
    $children = [];
    foreach ($node->childNodes as $childNode) {
      if ($childNode instanceof DOMElement) {
        $children[] = $childNode;
      }
    }
    return $children;
  }


  /**
   * Check whether node has element children.
   *
   * @param  DOMElement $node XML node
   * @return bool             True if complex node
   */
  private function hasElementChildren(DOMElement $node): bool {
    foreach ($node->childNodes as $childNode) {
      if ($childNode instanceof DOMElement) {
        return true;
      }
    }
    return false;
  }
}
