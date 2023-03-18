<?php
namespace josemmo\Facturae\FacturaeTraits;

use josemmo\Facturae\Common\XmlTools;
use josemmo\Facturae\CorrectiveDetails;
use josemmo\Facturae\FacturaePayment;
use josemmo\Facturae\ReimbursableExpense;

/**
 * Allows a Facturae instance to be exported to XML.
 */
trait ExportableTrait {

  /**
   * Add optional fields
   * @param  object   $item   Subject item
   * @param  string[] $fields Optional fields
   * @return string           Output XML
   */
  private function addOptionalFields($item, $fields) {
    $res = "";
    foreach ($fields as $key=>$name) {
      if (is_int($key)) $key = $name; // Allow $item to have a different property name
      if (!empty($item[$key])) {
        $xmlTag = ucfirst($name);
        $res .= "<$xmlTag>" . XmlTools::escape($item[$key]) . "</$xmlTag>";
      }
    }
    return $res;
  }


  /**
   * Export
   * Get Facturae XML data
   * @param  string     $filePath Path to save invoice
   * @return string|int           XML data|Written file bytes
   */
  public function export($filePath=null) {
    // Notify extensions
    foreach ($this->extensions as $ext) $ext->__onBeforeExport();

    // Prepare document
    $xml = '<fe:Facturae xmlns:fe="' . self::$SCHEMA_NS[$this->version] . '">';
    $totals = $this->getTotals();
    /** @var CorrectiveDetails|null */
    $corrective = $this->getCorrective();
    $paymentDetailsXML = $this->getPaymentDetailsXML($totals);

    // Add header
    $batchIdentifier = $this->parties['seller']->taxNumber . $this->header['number'] . $this->header['serie'];
    $xml .= '<FileHeader>';
    $xml .= '<SchemaVersion>' . $this->version .'</SchemaVersion>';
    $xml .= '<Modality>I</Modality>';
    $xml .= '<InvoiceIssuerType>' . $this->header['issuerType'] . '</InvoiceIssuerType>';
    if (!is_null($this->parties['thirdParty'])) {
      $xml .= '<ThirdParty>' . $this->parties['thirdParty']->getXML(false) . '</ThirdParty>';
    }
    $xml .= '<Batch>' .
              '<BatchIdentifier>' . $batchIdentifier . '</BatchIdentifier>' .
              '<InvoicesCount>1</InvoicesCount>' .
              '<TotalInvoicesAmount>' .
                '<TotalAmount>' . $this->pad($totals['invoiceAmount'], 'InvoiceTotal') . '</TotalAmount>' .
              '</TotalInvoicesAmount>' .
              '<TotalOutstandingAmount>' .
                '<TotalAmount>' . $this->pad($totals['totalOutstandingAmount'], 'InvoiceTotal') . '</TotalAmount>' .
              '</TotalOutstandingAmount>' .
              '<TotalExecutableAmount>' .
                '<TotalAmount>' . $this->pad($totals['totalExecutableAmount'], 'InvoiceTotal') . '</TotalAmount>' .
              '</TotalExecutableAmount>' .
              '<InvoiceCurrencyCode>' . $this->currency . '</InvoiceCurrencyCode>' .
            '</Batch>';

    // Add factoring assignment data
    if (!is_null($this->parties['assignee'])) {
      $xml .= '<FactoringAssignmentData>';
      $xml .= '<Assignee>' . $this->parties['assignee']->getXML(false) . '</Assignee>';
      $xml .= $paymentDetailsXML;
      if (!is_null($this->header['assignmentClauses'])) {
        $xml .= '<FactoringAssignmentClauses>' .
                  XmlTools::escape($this->header['assignmentClauses']) .
                '</FactoringAssignmentClauses>';
      }
      $xml .= '</FactoringAssignmentData>';
    }

    // Close header
    $xml .= '</FileHeader>';

    // Add parties
    $xml .= '<Parties>' .
              '<SellerParty>' . $this->parties['seller']->getXML(true) . '</SellerParty>' .
              '<BuyerParty>' . $this->parties['buyer']->getXML(true) . '</BuyerParty>' .
            '</Parties>';

    // Add invoice data
    $xml .= '<Invoices><Invoice>';
    $xml .= '<InvoiceHeader>';
    $xml .= '<InvoiceNumber>' . XmlTools::escape($this->header['number']) . '</InvoiceNumber>';
    $xml .= '<InvoiceSeriesCode>' . XmlTools::escape($this->header['serie']) . '</InvoiceSeriesCode>';
    $xml .= '<InvoiceDocumentType>' . $this->header['type'] . '</InvoiceDocumentType>';
    $xml .= '<InvoiceClass>' . ($corrective === null ? 'OO' : 'OR') . '</InvoiceClass>';
    if ($corrective !== null) {
      $xml .= '<Corrective>';
      if ($corrective->invoiceNumber !== null) {
        $xml .= '<InvoiceNumber>' . XmlTools::escape($corrective->invoiceNumber) . '</InvoiceNumber>';
      }
      if ($corrective->invoiceSeriesCode !== null) {
        $xml .= '<InvoiceSeriesCode>' . XmlTools::escape($corrective->invoiceSeriesCode) . '</InvoiceSeriesCode>';
      }
      $xml .= '<ReasonCode>' . $corrective->reason . '</ReasonCode>';
      $xml .= '<ReasonDescription>' . XmlTools::escape($corrective->getReasonDescription()) . '</ReasonDescription>';
      if ($corrective->taxPeriodStart !== null && $corrective->taxPeriodEnd !== null) {
        $start = is_string($corrective->taxPeriodStart) ? strtotime($corrective->taxPeriodStart) : $corrective->taxPeriodStart;
        $end = is_string($corrective->taxPeriodEnd) ? strtotime($corrective->taxPeriodEnd) : $corrective->taxPeriodEnd;
        $xml .= '<TaxPeriod>' .
            '<StartDate>' . date('Y-m-d', $start) . '</StartDate>' .
            '<EndDate>' . date('Y-m-d', $end) . '</EndDate>' .
          '</TaxPeriod>';
      }
      $xml .= '<CorrectionMethod>' . $corrective->correctionMethod . '</CorrectionMethod>';
      $xml .= '<CorrectionMethodDescription>' .
          XmlTools::escape($corrective->getCorrectionMethodDescription()) .
        '</CorrectionMethodDescription>';
      $xml .= '</Corrective>';
    }
    $xml .= '</InvoiceHeader>';
    $xml .= '<InvoiceIssueData>';
    $xml .= '<IssueDate>' . date('Y-m-d', $this->header['issueDate']) . '</IssueDate>';
    if (!is_null($this->header['startDate'])) {
      $xml .= '<InvoicingPeriod>' .
          '<StartDate>' . date('Y-m-d', $this->header['startDate']) . '</StartDate>' .
          '<EndDate>' . date('Y-m-d', $this->header['endDate']) . '</EndDate>' .
        '</InvoicingPeriod>';
    }
    $xml .= '<InvoiceCurrencyCode>' . $this->currency . '</InvoiceCurrencyCode>';
    $xml .= '<TaxCurrencyCode>' . $this->currency . '</TaxCurrencyCode>';
    $xml .= '<LanguageName>' . $this->language . '</LanguageName>';
    $xml .= $this->addOptionalFields($this->header, [
      "description" => "InvoiceDescription",
      "receiverTransactionReference",
      "fileReference",
      "receiverContractReference"
    ]);
    $xml .= '</InvoiceIssueData>';

    // Add invoice taxes
    foreach (["taxesOutputs", "taxesWithheld"] as $taxesGroup) {
      if (count($totals[$taxesGroup]) == 0) continue;
      $xmlTag = ucfirst($taxesGroup); // Just capitalize variable name
      $xml .= "<$xmlTag>";
      foreach ($totals[$taxesGroup] as $type=>$taxRows) {
        foreach ($taxRows as $tax) {
          $xml .= '<Tax>' .
                    '<TaxTypeCode>' . $type . '</TaxTypeCode>' .
                    '<TaxRate>' . $this->pad($tax['rate'], 'Tax/TaxRate') . '</TaxRate>' .
                    '<TaxableBase>' .
                      '<TotalAmount>' . $this->pad($tax['base'], 'Tax/TaxableBase') . '</TotalAmount>' .
                    '</TaxableBase>' .
                    '<TaxAmount>' .
                      '<TotalAmount>' . $this->pad($tax['amount'], 'Tax/TaxAmount') . '</TotalAmount>' .
                    '</TaxAmount>';
          if ($tax['surcharge'] != 0) {
            $xml .= '<EquivalenceSurcharge>' . $this->pad($tax['surcharge'], 'Tax/EquivalenceSurcharge') . '</EquivalenceSurcharge>' .
                    '<EquivalenceSurchargeAmount>' .
                      '<TotalAmount>' . $this->pad($tax['surchargeAmount'], 'Tax/EquivalenceSurchargeAmount') . '</TotalAmount>' .
                    '</EquivalenceSurchargeAmount>';
          }
          $xml .= '</Tax>';
        }
      }
      $xml .= "</$xmlTag>";
    }

    // Add invoice totals
    $xml .= '<InvoiceTotals>';
    $xml .= '<TotalGrossAmount>' . $this->pad($totals['grossAmount'], 'TotalGrossAmount') . '</TotalGrossAmount>';

    // Add general discounts and charges
    $generalGroups = array(
      ['GeneralDiscounts', 'Discount'],
      ['GeneralSurcharges', 'Charge']
    );
    foreach (['generalDiscounts', 'generalCharges'] as $g=>$groupTag) {
      if (empty($totals[$groupTag])) continue;
      $xmlTag = $generalGroups[$g][1];
      $xml .= '<' . $generalGroups[$g][0] . '>';
      foreach ($totals[$groupTag] as $elem) {
        $xml .= "<$xmlTag>";
        $xml .= "<{$xmlTag}Reason>" . XmlTools::escape($elem['reason']) . "</{$xmlTag}Reason>";
        if (!is_null($elem['rate'])) {
          $xml .= "<{$xmlTag}Rate>" . $this->pad($elem['rate'], 'DiscountCharge/Rate') . "</{$xmlTag}Rate>";
        }
        $xml .="<{$xmlTag}Amount>" . $this->pad($elem['amount'], 'DiscountCharge/Amount') . "</{$xmlTag}Amount>";
        $xml .= "</$xmlTag>";
      }
      $xml .= '</' . $generalGroups[$g][0] . '>';
    }

    // Add some total amounts
    $xml .= '<TotalGeneralDiscounts>' . $this->pad($totals['totalGeneralDiscounts'], 'TotalGeneralDiscounts') . '</TotalGeneralDiscounts>';
    $xml .= '<TotalGeneralSurcharges>' . $this->pad($totals['totalGeneralCharges'], 'TotalGeneralSurcharges') . '</TotalGeneralSurcharges>';
    $xml .= '<TotalGrossAmountBeforeTaxes>' . $this->pad($totals['grossAmountBeforeTaxes'], 'TotalGrossAmountBeforeTaxes') . '</TotalGrossAmountBeforeTaxes>';
    $xml .= '<TotalTaxOutputs>' . $this->pad($totals['totalTaxesOutputs'], 'TotalTaxOutputs') . '</TotalTaxOutputs>';
    $xml .= '<TotalTaxesWithheld>' . $this->pad($totals['totalTaxesWithheld'], 'TotalTaxesWithheld') . '</TotalTaxesWithheld>';
    $xml .= '<InvoiceTotal>' . $this->pad($totals['invoiceAmount'], 'InvoiceTotal') . '</InvoiceTotal>';

    // Add reimbursable expenses
    if (!empty($this->reimbursableExpenses)) {
      $xml .= '<ReimbursableExpenses>';
      foreach ($this->reimbursableExpenses as $expense) { /** @var ReimbursableExpense $expense */
        $xml .= '<ReimbursableExpenses>';
        if ($expense->seller !== null) {
          $xml .= '<ReimbursableExpensesSellerParty>';
          $xml .= $expense->seller->getReimbursableExpenseXML();
          $xml .= '</ReimbursableExpensesSellerParty>';
        }
        if ($expense->buyer !== null) {
          $xml .= '<ReimbursableExpensesBuyerParty>';
          $xml .= $expense->buyer->getReimbursableExpenseXML();
          $xml .= '</ReimbursableExpensesBuyerParty>';
        }
        if ($expense->issueDate !== null) {
          $issueDate = is_string($expense->issueDate) ? strtotime($expense->issueDate) : $expense->issueDate;
          $xml .= '<IssueDate>' . date('Y-m-d', $issueDate) . '</IssueDate>';
        }
        if ($expense->invoiceNumber !== null) {
          $xml .= '<InvoiceNumber>' . XmlTools::escape($expense->invoiceNumber) . '</InvoiceNumber>';
        }
        if ($expense->invoiceSeriesCode !== null) {
          $xml .= '<InvoiceSeriesCode>' . XmlTools::escape($expense->invoiceSeriesCode) . '</InvoiceSeriesCode>';
        }
        $xml .= '<ReimbursableExpensesAmount>' . $this->pad($expense->amount, 'ReimbursableExpense/Amount') . '</ReimbursableExpensesAmount>';
        $xml .= '</ReimbursableExpenses>';
      }
      $xml .= '</ReimbursableExpenses>';
    }

    // Add more total amounts
    $xml .= '<TotalOutstandingAmount>' . $this->pad($totals['totalOutstandingAmount'], 'TotalOutstandingAmount') . '</TotalOutstandingAmount>';
    $xml .= '<TotalExecutableAmount>' . $this->pad($totals['totalExecutableAmount'], 'TotalExecutableAmount') . '</TotalExecutableAmount>';
    if (!empty($this->reimbursableExpenses)) {
      $xml .= '<TotalReimbursableExpenses>' . $this->pad($totals['totalReimbursableExpenses'], 'TotalReimbursableExpenses') . '</TotalReimbursableExpenses>';
    }
    $xml .= '</InvoiceTotals>';

    // Add invoice items
    $xml .= '<Items>';
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData($this);
      $xml .= '<InvoiceLine>';

      // Add optional fields
      $xml .= $this->addOptionalFields($item, [
        "issuerContractReference", "issuerContractDate",
        "issuerTransactionReference", "issuerTransactionDate",
        "receiverContractReference", "receiverContractDate",
        "receiverTransactionReference", "receiverTransactionDate",
        "fileReference", "fileDate", "sequenceNumber"
      ]);

      // Add required fields
      $xml .= '<ItemDescription>' . XmlTools::escape($item['name']) . '</ItemDescription>' .
        '<Quantity>' . $this->pad($item['quantity'], 'Item/Quantity') . '</Quantity>' .
        '<UnitOfMeasure>' . $item['unitOfMeasure'] . '</UnitOfMeasure>' .
        '<UnitPriceWithoutTax>' . $this->pad($item['unitPriceWithoutTax'], 'Item/UnitPriceWithoutTax') . '</UnitPriceWithoutTax>' .
        '<TotalCost>' . $this->pad($item['totalAmountWithoutTax'], 'Item/TotalCost') . '</TotalCost>';

      // Add discounts and charges
      $itemGroups = array(
        ['DiscountsAndRebates', 'Discount'],
        ['Charges', 'Charge']
      );
      foreach (['discounts', 'charges'] as $g=>$group) {
        if (empty($item[$group])) continue;
        $groupTag = $itemGroups[$g][1];
        $xml .= '<' . $itemGroups[$g][0] . '>';
        foreach ($item[$group] as $elem) {
          $xml .= "<$groupTag>";
          $xml .= "<{$groupTag}Reason>" . XmlTools::escape($elem['reason']) . "</{$groupTag}Reason>";
          if (!is_null($elem['rate'])) {
            $xml .= "<{$groupTag}Rate>" . $this->pad($elem['rate'], 'DiscountCharge/Rate') . "</{$groupTag}Rate>";
          }
          $xml .="<{$groupTag}Amount>" . $this->pad($elem['amount'], 'DiscountCharge/Amount') . "</{$groupTag}Amount>";
          $xml .= "</$groupTag>";
        }
        $xml .= '</' . $itemGroups[$g][0] . '>';
      }

      // Add gross amount
      $xml .= '<GrossAmount>' . $this->pad($item['grossAmount'], 'Item/GrossAmount') . '</GrossAmount>';

      // Add item taxes
      // NOTE: As you can see here, taxesWithheld is before taxesOutputs.
      // This is intentional, as most official administrations would mark the
      // invoice as invalid XML if the order is incorrect.
      foreach (["taxesWithheld", "taxesOutputs"] as $taxesGroup) {
        if (count($item[$taxesGroup]) == 0) continue;
        $xmlTag = ucfirst($taxesGroup); // Just capitalize variable name
        $xml .= "<$xmlTag>";
        foreach ($item[$taxesGroup] as $type=>$tax) {
          $xml .= '<Tax>' .
                    '<TaxTypeCode>' . $type . '</TaxTypeCode>' .
                    '<TaxRate>' . $this->pad($tax['rate'], 'Tax/TaxRate') . '</TaxRate>' .
                    '<TaxableBase>' .
                      '<TotalAmount>' . $this->pad($tax['base'], 'Tax/TaxableBase') . '</TotalAmount>' .
                    '</TaxableBase>' .
                    '<TaxAmount>' .
                      '<TotalAmount>' . $this->pad($tax['amount'], 'Tax/TaxAmount') . '</TotalAmount>' .
                    '</TaxAmount>';
          if ($tax['surcharge'] != 0) {
            $xml .= '<EquivalenceSurcharge>' . $this->pad($tax['surcharge'], 'Tax/EquivalenceSurcharge') . '</EquivalenceSurcharge>' .
                    '<EquivalenceSurchargeAmount>' .
                      '<TotalAmount>' .
                        $this->pad($tax['surchargeAmount'], 'Tax/EquivalenceSurchargeAmount') .
                      '</TotalAmount>' .
                    '</EquivalenceSurchargeAmount>';
          }
          $xml .= '</Tax>';
        }
        $xml .= "</$xmlTag>";
      }

      // Add line period dates
      if (!empty($item['periodStart']) && !empty($item['periodEnd'])) {
        $xml .= '<LineItemPeriod>';
        $xml .= '<StartDate>' . XmlTools::escape($item['periodStart']) . '</StartDate>';
        $xml .= '<EndDate>' . XmlTools::escape($item['periodEnd']) . '</EndDate>';
        $xml .= '</LineItemPeriod>';
      }

      // Add more optional fields
      $xml .= $this->addOptionalFields($item, ["description" => "AdditionalLineItemInformation"]);
      if (!is_null($item['specialTaxableEventCode']) && !is_null($item['specialTaxableEventReason'])) {
        $xml .= '<SpecialTaxableEvent>';
        $xml .= '<SpecialTaxableEventCode>' . XmlTools::escape($item['specialTaxableEventCode']) . '</SpecialTaxableEventCode>';
        $xml .= '<SpecialTaxableEventReason>' . XmlTools::escape($item['specialTaxableEventReason']) . '</SpecialTaxableEventReason>';
        $xml .= '</SpecialTaxableEvent>';
      }
      $xml .= $this->addOptionalFields($item, ["articleCode"]);

      // Close invoice line
      $xml .= '</InvoiceLine>';
    }
    $xml .= '</Items>';

    // Add payment details
    $xml .= $paymentDetailsXML;

    // Add legal literals
    if (count($this->legalLiterals) > 0) {
      $xml .= '<LegalLiterals>';
      foreach ($this->legalLiterals as $reference) {
        $xml .= '<LegalReference>' . XmlTools::escape($reference) . '</LegalReference>';
      }
      $xml .= '</LegalLiterals>';
    }

    // Add additional data
    $xml .= $this->getAdditionalDataXML();

    // Close invoice and document
    $xml .= '</Invoice></Invoices></fe:Facturae>';
    foreach ($this->extensions as $ext) $xml = $ext->__onBeforeSign($xml);

    // Add signature and timestamp
    $xml = $this->injectSignatureAndTimestamp($xml);
    foreach ($this->extensions as $ext) $xml = $ext->__onAfterSign($xml);

    // Prepend content type
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;

    // Save document
    if (!is_null($filePath)) return file_put_contents($filePath, $xml);
    return $xml;
  }


  /**
   * Get payment details XML
   * @param  array  $totals Invoice totals
   * @return string         Payment details XML, empty string if not available
   */
  private function getPaymentDetailsXML($totals) {
    if (empty($this->payments)) return "";

    $xml  = '<PaymentDetails>';
    /** @var FacturaePayment $payment */
    foreach ($this->payments as $payment) {
      $dueDate = is_null($payment->dueDate) ?
        $this->header['issueDate'] :
        (is_string($payment->dueDate) ? strtotime($payment->dueDate) : $payment->dueDate);
      $amount = is_null($payment->amount) ? $totals['invoiceAmount'] : $payment->amount;
      $xml .= '<Installment>';
      $xml .= '<InstallmentDueDate>' . date('Y-m-d', $dueDate) . '</InstallmentDueDate>';
      $xml .= '<InstallmentAmount>' . $this->pad($amount, 'InvoiceTotal') . '</InstallmentAmount>';
      $xml .= '<PaymentMeans>' . $payment->method . '</PaymentMeans>';
      if (!is_null($payment->iban)) {
        $accountType = ($payment->method == FacturaePayment::TYPE_DEBIT) ? "AccountToBeDebited" : "AccountToBeCredited";
        $xml .= "<$accountType>";
        $xml .= '<IBAN>' . preg_replace('/[^A-Z0-9]/', '', $payment->iban) . '</IBAN>';
        if (!is_null($payment->bic)) {
          $xml .= '<BIC>' . str_pad(preg_replace('/[^A-Z0-9]/', '', $payment->bic), 11, 'X') . '</BIC>';
        }
        $xml .= "</$accountType>";
      }
      $xml .= '</Installment>';
    }
    $xml .= '</PaymentDetails>';

    return $xml;
  }


  /**
   * Get additional data XML
   * @return string Additional data XML
   */
  private function getAdditionalDataXML() {
    $extensionsXML = array();
    foreach ($this->extensions as $ext) {
      $extXML = $ext->__getAdditionalData();
      if (!empty($extXML)) $extensionsXML[] = $extXML;
    }
    $relInvoice =& $this->header['relatedInvoice'];
    $additionalInfo =& $this->header['additionalInformation'];

    // Validate additional data fields
    $hasData = !empty($extensionsXML) || !empty($this->attachments) || !empty($relInvoice) || !empty($additionalInfo);
    if (!$hasData) return "";

    // Generate initial XML block
    $xml = '<AdditionalData>';
    if (!empty($relInvoice)) $xml .= '<RelatedInvoice>' . XmlTools::escape($relInvoice) . '</RelatedInvoice>';

    // Add attachments
    if (!empty($this->attachments)) {
      $xml .= '<RelatedDocuments>';
      foreach ($this->attachments as $att) {
        $type = explode('/', $att['file']->getMimeType());
        $type = end($type);
        $xml .= '<Attachment>';
        $xml .= '<AttachmentCompressionAlgorithm>NONE</AttachmentCompressionAlgorithm>';
        $xml .= '<AttachmentFormat>' . XmlTools::escape($type) . '</AttachmentFormat>';
        $xml .= '<AttachmentEncoding>BASE64</AttachmentEncoding>';
        $xml .= '<AttachmentDescription>' . XmlTools::escape($att['description']) . '</AttachmentDescription>';
        $xml .= '<AttachmentData>' . base64_encode($att['file']->getData()) . '</AttachmentData>';
        $xml .= '</Attachment>';
      }
      $xml .= '</RelatedDocuments>';
    }

    // Add additional information
    if (!empty($additionalInfo)) {
      $xml .= '<InvoiceAdditionalInformation>' . XmlTools::escape($additionalInfo) . '</InvoiceAdditionalInformation>';
    }

    // Add extensions data
    if (!empty($extensionsXML)) $xml .= '<Extensions>' . implode('', $extensionsXML) . '</Extensions>';

    $xml .= '</AdditionalData>';
    return $xml;
  }

}
