<?php
namespace josemmo\Facturae\FacturaeTraits;

use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;

/**
 * Allows a Facturae instance to be imported from XML.
 */
trait ImportableTrait {
    /** @var string|null */
    protected $facturaeNamespaceUri = null;

    /** @var array|null */
    protected $importedInvoiceTaxes = null;

    /**
     * Build a Facturae instance from a Facturae XML string.
     *
     * @param  string $xmlContent Facturae XML content
     * @return self               Hydrated Facturae instance
     */
    public static function fromXml(string $xmlContent): self {
        $instance = new self();
        $xml = new \SimpleXMLElement($xmlContent);
        $instance->registerFacturaeNamespace($xml);

        $schemaVersion = self::xpathString($xml, '//*[local-name()="FileHeader"]/*[local-name()="SchemaVersion"]');
        if (!empty($schemaVersion)) {
            $instance->setSchemaVersion($schemaVersion);
        }

        $instance->setIssuerType(self::xpathString($xml, '//*[local-name()="FileHeader"]/*[local-name()="InvoiceIssuerType"]') ?: $instance->getIssuerType());
        $invoiceHeader = self::xpathNode($xml, '//*[local-name()="Invoices"]/*[local-name()="Invoice"]/*[local-name()="InvoiceHeader"]');
        if ($invoiceHeader !== null) {
            $invoiceType = self::childString($invoiceHeader, 'InvoiceDocumentType');
            if (!empty($invoiceType)) {
                $instance->setType($invoiceType);
            }

            $number = self::childString($invoiceHeader, 'InvoiceNumber');
            $serie = self::childString($invoiceHeader, 'InvoiceSeriesCode');
            if (!empty($number) || !empty($serie)) {
                $instance->setNumber($serie, $number);
            }
        }

        $issueData = self::xpathNode($xml, '//*[local-name()="Invoices"]/*[local-name()="Invoice"]/*[local-name()="InvoiceIssueData"]');
        if ($issueData !== null) {
            $issueDate = self::childString($issueData, 'IssueDate');
            if (!empty($issueDate)) {
                $instance->setIssueDate($issueDate);
            }

            $period = self::childNode($issueData, 'InvoicingPeriod');
            if ($period !== null) {
                $periodStart = self::childString($period, 'StartDate');
                $periodEnd = self::childString($period, 'EndDate');
                if (!empty($periodStart) && !empty($periodEnd)) {
                    $instance->setBillingPeriod($periodStart, $periodEnd);
                }
            }

            $currency = self::childString($issueData, 'InvoiceCurrencyCode');
            if (!empty($currency)) {
                $instance->currency = $currency;
            }

            $language = self::childString($issueData, 'LanguageName');
            if (!empty($language)) {
                $instance->language = $language;
            }

            $description = self::childString($issueData, 'InvoiceDescription');
            if (!empty($description)) {
                $instance->setDescription($description);
            }

            $instance->setReferences(
                self::childString($issueData, 'FileReference'),
                self::childString($issueData, 'ReceiverTransactionReference'),
                self::childString($issueData, 'ReceiverContractReference')
            );
        }

        $sellerNode = self::xpathNode($xml, '//*[local-name()="Parties"]/*[local-name()="SellerParty"]');
        if ($sellerNode !== null) {
            $instance->setSeller(self::parseParty($sellerNode));
        }

        $buyerNode = self::xpathNode($xml, '//*[local-name()="Parties"]/*[local-name()="BuyerParty"]');
        if ($buyerNode !== null) {
            $instance->setBuyer(self::parseParty($buyerNode));
        }

        $lines = $xml->xpath('//*[local-name()="Invoices"]/*[local-name()="Invoice"]/*[local-name()="Items"]/*[local-name()="InvoiceLine"]');
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $instance->addItem(self::parseInvoiceLine($line));
            }
        }

        $instance->importedInvoiceTaxes = self::parseInvoiceTaxes($xml);
        return $instance;
    }

    /**
     * Backward-compatible import alias.
     *
     * @param  string $xmlContent Facturae XML content
     * @return self               Hydrated Facturae instance
     */
    public static function import($xmlContent) {
        return static::fromXml($xmlContent);
    }

    /**
     * Export imported and computed data to JSON.
     *
     * @return string JSON representation
     */
    public function toJson(): string {
        $number = $this->getNumber();
        $items = [];
        foreach ($this->getItems() as $itemObj) {
            $itemData = $itemObj->getData($this);
            $taxes = [];
            foreach (['taxesOutputs', 'taxesWithheld'] as $taxGroup) {
                foreach ($itemData[$taxGroup] as $taxType=>$taxData) {
                    $taxes[] = [
                        'TaxType' => $taxType,
                        'TaxRate' => (float) $taxData['rate'],
                        'TaxBase' => (float) $taxData['base'],
                        'TotalTaxAmount' => (float) $taxData['amount']
                    ];
                }
            }

            $items[] = [
                'Name' => $itemData['name'],
                'Description' => $itemData['description'],
                'Quantity' => (float) $itemData['quantity'],
                'UnitOfMeasure' => $itemData['unitOfMeasure'],
                'UnitPriceWithoutTax' => (float) $itemData['unitPriceWithoutTax'],
                'TotalCost' => (float) $itemData['totalAmountWithoutTax'],
                'Taxes' => $taxes
            ];
        }

        $totals = $this->getTotals();
        $taxBreakdown = [];
        foreach (['taxesOutputs', 'taxesWithheld'] as $taxGroup) {
            foreach ($totals[$taxGroup] as $taxType=>$taxRows) {
                foreach ($taxRows as $taxRow) {
                    $taxBreakdown[] = [
                        'TaxGroup' => $taxGroup,
                        'TaxType' => $taxType,
                        'TaxRate' => (float) $taxRow['rate'],
                        'TaxBase' => (float) $taxRow['base'],
                        'TotalTaxAmount' => (float) $taxRow['amount']
                    ];
                }
            }
        }

        $payload = [
            'header' => [
                'schemaVersion' => $this->getSchemaVersion(),
                'issuerType' => $this->getIssuerType(),
                'invoiceType' => $this->getType(),
                'number' => $number['number'],
                'serie' => $number['serie'],
                'issueDate' => $this->getIssueDate(),
                'description' => $this->getDescription(),
                'currency' => $this->currency,
                'language' => $this->language
            ],
            'parties' => [
                'seller' => self::partyToArray($this->getSeller()),
                'buyer' => self::partyToArray($this->getBuyer())
            ],
            'items' => $items,
            'totals' => [
                'grossAmount' => (float) $totals['grossAmount'],
                'grossAmountBeforeTaxes' => (float) $totals['grossAmountBeforeTaxes'],
                'invoiceAmount' => (float) $totals['invoiceAmount'],
                'totalTaxesOutputs' => (float) $totals['totalTaxesOutputs'],
                'totalTaxesWithheld' => (float) $totals['totalTaxesWithheld'],
                'taxBreakdown' => $taxBreakdown,
                'invoiceTaxBreakdownImported' => $this->importedInvoiceTaxes
            ]
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Register Facturae namespace for XPath operations.
     *
     * @param \SimpleXMLElement $xml Root XML element
     */
    private function registerFacturaeNamespace(\SimpleXMLElement $xml) {
        $namespaces = $xml->getDocNamespaces(true);
        $namespaceUri = null;

        foreach ($namespaces as $uri) {
            if (strpos($uri, 'facturae') !== false || strpos($uri, 'Facturae') !== false) {
                $namespaceUri = $uri;
                break;
            }
        }

        if ($namespaceUri === null) {
            if (isset($namespaces[''])) {
                $namespaceUri = $namespaces[''];
            } elseif (!empty($namespaces)) {
                $first = array_values($namespaces);
                $namespaceUri = $first[0];
            }
        }

        if ($namespaceUri !== null) {
            $this->facturaeNamespaceUri = $namespaceUri;
            $xml->registerXPathNamespace('f', $namespaceUri);
        }
    }

    /**
     * Parse a SellerParty/BuyerParty node.
     *
     * @param  \SimpleXMLElement $node Party XML node
     * @return FacturaeParty            Party instance
     */
    private static function parseParty(\SimpleXMLElement $node) {
        $party = new FacturaeParty();
        $taxIdentification = self::childNode($node, 'TaxIdentification');

        if ($taxIdentification !== null) {
            $party->taxNumber = self::childString($taxIdentification, 'TaxIdentificationNumber');
            $party->isLegalEntity = self::childString($taxIdentification, 'PersonTypeCode') !== 'F';
        }

        $legalEntity = self::childNode($node, 'LegalEntity');
        $individual = self::childNode($node, 'Individual');
        if ($legalEntity !== null) {
            $party->name = self::childString($legalEntity, 'CorporateName');
            $party->tradeName = self::childString($legalEntity, 'TradeName');
            self::hydrateAddressAndContact($party, $legalEntity);
        } elseif ($individual !== null) {
            $party->isLegalEntity = false;
            $party->name = self::childString($individual, 'Name');
            $party->firstSurname = self::childString($individual, 'FirstSurname');
            $party->lastSurname = self::childString($individual, 'SecondSurname');
            self::hydrateAddressAndContact($party, $individual);
        }

        return $party;
    }

    /**
     * Parse an invoice line into a FacturaeItem instance.
     *
     * @param  \SimpleXMLElement $line Invoice line node
     * @return FacturaeItem             Item instance
     */
    private static function parseInvoiceLine(\SimpleXMLElement $line) {
        $taxesOutputs = self::parseLineTaxGroup($line, 'TaxesOutputs');
        $taxesWithheld = self::parseLineTaxGroup($line, 'TaxesWithheld');

        return new FacturaeItem([
            'name' => self::childString($line, 'ItemDescription'),
            'description' => self::childString($line, 'AdditionalLineItemInformation'),
            'quantity' => (float) self::childString($line, 'Quantity'),
            'unitOfMeasure' => self::childString($line, 'UnitOfMeasure'),
            'unitPriceWithoutTax' => (float) self::childString($line, 'UnitPriceWithoutTax'),
            'taxesOutputs' => $taxesOutputs,
            'taxesWithheld' => $taxesWithheld,
            'specialTaxableEventCode' => self::childString(self::childNode($line, 'SpecialTaxableEvent'), 'SpecialTaxableEventCode'),
            'specialTaxableEventReason' => self::childString(self::childNode($line, 'SpecialTaxableEvent'), 'SpecialTaxableEventReason'),
            'articleCode' => self::childString($line, 'ArticleCode')
        ]);
    }

    /**
     * Parse taxes from an invoice line tax group node.
     *
     * @param  \SimpleXMLElement $line      Invoice line node
     * @param  string             $groupName Tax group tag name
     * @return array                         Parsed taxes
     */
    private static function parseLineTaxGroup(\SimpleXMLElement $line, $groupName) {
        $groupNode = self::childNode($line, $groupName);
        if ($groupNode === null) {
            return [];
        }

        $result = [];
        $taxNodes = $groupNode->xpath('./*[local-name()="Tax"]');
        foreach ($taxNodes as $taxNode) {

            $taxType = self::childString($taxNode, 'TaxTypeCode');
            if (empty($taxType)) {
                continue;
            }

            $result[$taxType] = [
                'rate' => (float) self::childString($taxNode, 'TaxRate'),
                'base' => (float) self::childString(self::childNode($taxNode, 'TaxableBase'), 'TotalAmount'),
                'amount' => (float) self::childString(self::childNode($taxNode, 'TaxAmount'), 'TotalAmount'),
                'surcharge' => (float) self::childString($taxNode, 'EquivalenceSurcharge'),
                'surchargeAmount' => (float) self::childString(self::childNode($taxNode, 'EquivalenceSurchargeAmount'), 'TotalAmount')
            ];
        }

        return $result;
    }

    /**
     * Parse invoice-level tax totals from TaxesOutputs/TaxesWithheld.
     *
     * @param  \SimpleXMLElement $xml Root XML node
     * @return array|null              Imported tax rows
     */
    private static function parseInvoiceTaxes(\SimpleXMLElement $xml) {
        $taxes = [];
        $groups = [
            'TaxesOutputs' => 'taxesOutputs',
            'TaxesWithheld' => 'taxesWithheld'
        ];

        foreach ($groups as $tag=>$groupKey) {
            $nodes = $xml->xpath('//*[local-name()="Invoices"]/*[local-name()="Invoice"]/*[local-name()="' . $tag . '"]/*[local-name()="Tax"]');
            if (empty($nodes)) {
                continue;
            }

            foreach ($nodes as $taxNode) {
                $taxes[] = [
                    'TaxGroup' => $groupKey,
                    'TaxType' => self::childString($taxNode, 'TaxTypeCode'),
                    'TaxRate' => (float) self::childString($taxNode, 'TaxRate'),
                    'TaxBase' => (float) self::childString(self::childNode($taxNode, 'TaxableBase'), 'TotalAmount'),
                    'TotalTaxAmount' => (float) self::childString(self::childNode($taxNode, 'TaxAmount'), 'TotalAmount')
                ];
            }
        }

        return empty($taxes) ? null : $taxes;
    }

    /**
     * Hydrate party address and contact details.
     *
     * @param FacturaeParty      $party Target party
     * @param \SimpleXMLElement  $node  Node containing address/contact
     */
    private static function hydrateAddressAndContact(FacturaeParty $party, \SimpleXMLElement $node) {
        $address = self::childNode($node, 'AddressInSpain');
        if ($address !== null) {
            $party->address = self::childString($address, 'Address');
            $party->postCode = self::childString($address, 'PostCode');
            $party->town = self::childString($address, 'Town');
            $party->province = self::childString($address, 'Province');
            $party->countryCode = self::childString($address, 'CountryCode') ?: 'ESP';
        } else {
            $address = self::childNode($node, 'OverseasAddress');
            if ($address !== null) {
                $postCodeAndTown = trim(self::childString($address, 'PostCodeAndTown'));
                $party->address = self::childString($address, 'Address');
                $party->countryCode = self::childString($address, 'CountryCode');
                $party->province = self::childString($address, 'Province');

                if ($postCodeAndTown !== '') {
                    $parts = preg_split('/\s+/', $postCodeAndTown, 2);
                    $party->postCode = $parts[0];
                    $party->town = isset($parts[1]) ? $parts[1] : null;
                }
            }
        }

        $contact = self::childNode($node, 'ContactDetails');
        if ($contact !== null) {
            $party->phone = self::childString($contact, 'Telephone');
            $party->fax = self::childString($contact, 'TeleFax');
            $party->website = self::childString($contact, 'WebAddress');
            $party->email = self::childString($contact, 'ElectronicMail');
            $party->contactPeople = self::childString($contact, 'ContactPersons');
            $party->cnoCnae = self::childString($contact, 'CnoCnae');
            $party->ineTownCode = self::childString($contact, 'INETownCode');
        }
    }

    /**
     * Convert party object to serializable array.
     *
     * @param  FacturaeParty|null $party Party object
     * @return array|null                Party as array
     */
    private static function partyToArray($party) {
        if ($party === null) {
            return null;
        }

        return [
            'isLegalEntity' => (bool) $party->isLegalEntity,
            'taxNumber' => $party->taxNumber,
            'name' => $party->name,
            'tradeName' => $party->tradeName,
            'firstSurname' => $party->firstSurname,
            'lastSurname' => $party->lastSurname,
            'address' => $party->address,
            'postCode' => $party->postCode,
            'town' => $party->town,
            'province' => $party->province,
            'countryCode' => $party->countryCode,
            'email' => $party->email,
            'phone' => $party->phone,
            'fax' => $party->fax,
            'website' => $party->website,
            'contactPeople' => $party->contactPeople
        ];
    }

    /**
     * Get first node that matches XPath.
     *
     * @param  \SimpleXMLElement $xml   XML root
     * @param  string             $xpath XPath expression
     * @return \SimpleXMLElement|null   Node or null
     */
    private static function xpathNode(\SimpleXMLElement $xml, $xpath) {
        $result = $xml->xpath($xpath);
        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    /**
     * Get string value of first node that matches XPath.
     *
     * @param  \SimpleXMLElement $xml   XML root
     * @param  string             $xpath XPath expression
     * @return string|null               Node text content
     */
    private static function xpathString(\SimpleXMLElement $xml, $xpath) {
        $node = self::xpathNode($xml, $xpath);
        if ($node === null) {
            return null;
        }

        $value = trim((string) $node);
        return $value === '' ? null : $value;
    }

    /**
     * Get direct child node by local name.
     *
     * @param  \SimpleXMLElement      $node      Parent node
     * @param  string                  $localName Child local name
     * @return \SimpleXMLElement|null            Child node
     */
    private static function childNode($node, $localName) {
        if ($node === null) {
            return null;
        }

        $children = $node->xpath('./*[local-name()="' . $localName . '"]');
        if (!empty($children)) {
            return $children[0];
        }

        return null;
    }

    /**
     * Get direct child node value as string.
     *
     * @param  \SimpleXMLElement|null $node      Parent node
     * @param  string                  $localName Child local name
     * @return string|null                        Child text
     */
    private static function childString($node, $localName) {
        $child = self::childNode($node, $localName);
        if ($child === null) {
            return null;
        }

        $value = trim((string) $child);
        return $value === '' ? null : $value;
    }
}
