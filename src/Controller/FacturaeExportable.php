<?php
namespace josemmo\Facturae\Controller;

/**
 * Allows a @link{josemmo\Facturae\Facturae} instance to be exported to XML.
 */
abstract class FacturaeExportable extends FacturaeSignable {

  /**
   * Export
   *
   * Get Facturae XML data
   *
   * @param  string     $filePath Path to save invoice
   * @return string|int           XML data|Written file bytes
   */
  public function export($filePath=null) {
    // Prepare document
    $xml = '<fe:Facturae xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ' .
           'xmlns:fe="' . self::$SCHEMA_NS[$this->version] . '">';
    $totals = $this->getTotals();

    // Add header
    $batchIdentifier = $this->parties['seller']->taxNumber .
      $this->header['number'] . $this->header['serie'];
    $xml .= '<FileHeader>' .
              '<SchemaVersion>' . $this->version .'</SchemaVersion>' .
              '<Modality>I</Modality>' .
              '<InvoiceIssuerType>EM</InvoiceIssuerType>' .
              '<Batch>' .
                '<BatchIdentifier>' . $batchIdentifier . '</BatchIdentifier>' .
                '<InvoicesCount>1</InvoicesCount>' .
                '<TotalInvoicesAmount>' .
                  '<TotalAmount>' . $this->pad($totals['invoiceAmount']) . '</TotalAmount>' .
                '</TotalInvoicesAmount>' .
                '<TotalOutstandingAmount>' .
                  '<TotalAmount>' . $this->pad($totals['invoiceAmount']) . '</TotalAmount>' .
                '</TotalOutstandingAmount>' .
                '<TotalExecutableAmount>' .
                  '<TotalAmount>' . $this->pad($totals['invoiceAmount']) . '</TotalAmount>' .
                '</TotalExecutableAmount>' .
                '<InvoiceCurrencyCode>' . $this->currency . '</InvoiceCurrencyCode>' .
              '</Batch>' .
            '</FileHeader>';

    // Add parties
    $xml .= '<Parties>' .
              '<SellerParty>' . $this->parties['seller']->getXML($this->version) . '</SellerParty>' .
              '<BuyerParty>' . $this->parties['buyer']->getXML($this->version) . '</BuyerParty>' .
            '</Parties>';

    // Add invoice data
    $xml .= '<Invoices>' .
              '<Invoice>' .
                '<InvoiceHeader>' .
                  '<InvoiceNumber>' . $this->header['number'] . '</InvoiceNumber>' .
                  '<InvoiceSeriesCode>' . $this->header['serie'] . '</InvoiceSeriesCode>' .
                  '<InvoiceDocumentType>FC</InvoiceDocumentType>' .
                  '<InvoiceClass>OO</InvoiceClass>' .
                '</InvoiceHeader>' .
                '<InvoiceIssueData>' .
                  '<IssueDate>' . date('Y-m-d', $this->header['issueDate']) . '</IssueDate>';
    if (!is_null($this->header['startDate'])) {
      $xml .=     '<InvoicingPeriod>' .
                    '<StartDate>' . date('Y-m-d', $this->header['startDate']) . '</StartDate>' .
                    '<EndDate>' . date('Y-m-d', $this->header['endDate']) . '</EndDate>' .
                  '</InvoicingPeriod>';
    }
    $xml .=       '<InvoiceCurrencyCode>' . $this->currency . '</InvoiceCurrencyCode>' .
                  '<TaxCurrencyCode>' . $this->currency . '</TaxCurrencyCode>' .
                  '<LanguageName>es</LanguageName>' .
                '</InvoiceIssueData>';

    // Add invoice taxes
    foreach (["taxesOutputs", "taxesWithheld"] as $i=>$taxesGroup) {
      if (count($totals[$taxesGroup]) == 0) continue;
      $xmlTag = ucfirst($taxesGroup); // Just capitalize variable name
      $xml .= "<$xmlTag>";
      foreach ($totals[$taxesGroup] as $type=>$taxRows) {
        foreach ($taxRows as $rate=>$tax) {
          $xml .= '<Tax>' .
                    '<TaxTypeCode>' . $type . '</TaxTypeCode>' .
                    '<TaxRate>' . $this->pad($rate) . '</TaxRate>' .
                    '<TaxableBase>' .
                      '<TotalAmount>' . $this->pad($tax['base']) . '</TotalAmount>' .
                    '</TaxableBase>' .
                    '<TaxAmount>' .
                      '<TotalAmount>' . $this->pad($tax['amount']) . '</TotalAmount>' .
                    '</TaxAmount>' .
                  '</Tax>';
        }
      }
      $xml .= "</$xmlTag>";
    }

    // Add invoice totals
    $xml .= '<InvoiceTotals>' .
              '<TotalGrossAmount>' . $this->pad($totals['grossAmount']) . '</TotalGrossAmount>' .
              '<TotalGeneralDiscounts>0.00</TotalGeneralDiscounts>' .
              '<TotalGeneralSurcharges>0.00</TotalGeneralSurcharges>' .
              '<TotalGrossAmountBeforeTaxes>' . $this->pad($totals['grossAmountBeforeTaxes']) . '</TotalGrossAmountBeforeTaxes>' .
              '<TotalTaxOutputs>' . $this->pad($totals['totalTaxesOutputs']) . '</TotalTaxOutputs>' .
              '<TotalTaxesWithheld>' . $this->pad($totals['totalTaxesWithheld']) . '</TotalTaxesWithheld>' .
              '<InvoiceTotal>' . $this->pad($totals['invoiceAmount']) . '</InvoiceTotal>' .
              '<TotalOutstandingAmount>' . $this->pad($totals['invoiceAmount']) . '</TotalOutstandingAmount>' .
              '<TotalExecutableAmount>' . $this->pad($totals['invoiceAmount']) . '</TotalExecutableAmount>' .
            '</InvoiceTotals>';

    // Add invoice items
    $xml .= '<Items>';
    foreach ($this->items as $itemObj) {
      $item = $itemObj->getData();
      $xml .= '<InvoiceLine>' .
                '<ItemDescription>' . $item['name'] . '</ItemDescription>' .
                '<Quantity>' . $this->pad($item['quantity']) . '</Quantity>' .
                '<UnitOfMeasure>' . $item['unitOfMeasure'] . '</UnitOfMeasure>' .
                '<UnitPriceWithoutTax>' . $this->pad($item['unitPriceWithoutTax'], 'UnitPriceWithoutTax') . '</UnitPriceWithoutTax>' .
                '<TotalCost>' . $this->pad($item['totalAmountWithoutTax'], 'TotalCost') . '</TotalCost>' .
                '<GrossAmount>' . $this->pad($item['grossAmount'], 'GrossAmount') . '</GrossAmount>';

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
                    '<TaxRate>' . $this->pad($tax['rate']) . '</TaxRate>' .
                    '<TaxableBase>' .
                      '<TotalAmount>' . $this->pad($item['totalAmountWithoutTax']) . '</TotalAmount>' .
                    '</TaxableBase>' .
                    '<TaxAmount>' .
                      '<TotalAmount>' . $this->pad($tax['amount']) . '</TotalAmount>' .
                    '</TaxAmount>' .
                  '</Tax>';
        }
        $xml .= "</$xmlTag>";
      }

      // Add item additional information
      if (!is_null($item['description'])) {
        $xml .= '<AdditionalLineItemInformation>' . $item['description'] . '</AdditionalLineItemInformation>';
      }
      $xml .= '</InvoiceLine>';
    }
    $xml .= '</Items>';

    // Add payment details
    if (!is_null($this->header['paymentMethod'])) {
      $dueDate = is_null($this->header['dueDate']) ?
        $this->header['issueDate'] :
        $this->header['dueDate'];
      $xml .= '<PaymentDetails>' .
                '<Installment>' .
                  '<InstallmentDueDate>' . date('Y-m-d', $dueDate) . '</InstallmentDueDate>' .
                  '<InstallmentAmount>' . $this->pad($totals['invoiceAmount']) . '</InstallmentAmount>' .
                  '<PaymentMeans>' . $this->header['paymentMethod'] . '</PaymentMeans>';
      if ($this->header['paymentMethod'] == self::PAYMENT_TRANSFER) {
        $xml .=   '<AccountToBeCredited>' .
                    '<IBAN>' . $this->header['paymentIBAN'] . '</IBAN>' .
                  '</AccountToBeCredited>';
      }
      $xml .=   '</Installment>' .
              '</PaymentDetails>';
    }

    // Add legal literals
    if (count($this->legalLiterals) > 0) {
      $xml .= '<LegalLiterals>';
      foreach ($this->legalLiterals as $reference) {
        $xml .= '<LegalReference>' . $reference . '</LegalReference>';
      }
      $xml .= '</LegalLiterals>';
    }

    // Add additional data
    $extensionsXML = array();
    foreach ($this->extensions as $ext) {
      $extXML = $ext->__getAdditionalData();
      if (!empty($extXML)) $extensionsXML[] = $extXML;
    }
    if (count($extensionsXML) > 0) {
      $xml .= '<AdditionalData><Extensions>';
      $xml .= implode("", $extensionsXML);
      $xml .= '</Extensions></AdditionalData>';
    }

    // Close invoice and document
    $xml .= '</Invoice></Invoices></fe:Facturae>';
    foreach ($this->extensions as $ext) $xml = $ext->__onBeforeSign($xml);

    // Add signature
    $xml = $this->injectSignature($xml);
    foreach ($this->extensions as $ext) $xml = $ext->__onAfterSign($xml);

    // Prepend content type
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;

    // Save document
    if (!is_null($filePath)) return file_put_contents($filePath, $xml);
    return $xml;
  }

}
