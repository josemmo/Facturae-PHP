<?php
namespace josemmo\Facturae;

use josemmo\Facturae\FacturaeTraits\PropertiesTrait;
use josemmo\Facturae\FacturaeTraits\UtilsTrait;
use josemmo\Facturae\FacturaeTraits\SignableTrait;
use josemmo\Facturae\FacturaeTraits\ExportableTrait;

/**
 * Class for creating electronic invoices that comply with the Spanish FacturaE format.
 */
class Facturae {
  const VERSION = "1.6.1";
  const USER_AGENT = "FacturaePHP/" . self::VERSION;

  const SCHEMA_3_2 = "3.2";
  const SCHEMA_3_2_1 = "3.2.1";
  const SCHEMA_3_2_2 = "3.2.2";
  const SIGN_POLICY_3_1 = array(
    "name" => "Política de Firma FacturaE v3.1",
    "url" => "http://www.facturae.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf",
    "digest" => "Ohixl6upD6av8N7pEvDABhEL6hM="
  );

  const PAYMENT_CASH = "01";
  const PAYMENT_DEBIT = "02";
  const PAYMENT_RECEIPT = "03";
  const PAYMENT_TRANSFER = "04";
  const PAYMENT_ACCEPTED_BILL_OF_EXCHANGE = "05";
  const PAYMENT_DOCUMENTARY_CREDIT = "06";
  const PAYMENT_CONTRACT_AWARD = "07";
  const PAYMENT_BILL_OF_EXCHANGE = "08";
  const PAYMENT_TRANSFERABLE_IOU = "09";
  const PAYMENT_IOU = "10";
  const PAYMENT_CHEQUE = "11";
  const PAYMENT_REIMBURSEMENT = "12";
  const PAYMENT_SPECIAL = "13";
  const PAYMENT_SETOFF = "14";
  const PAYMENT_POSTGIRO = "15";
  const PAYMENT_CERTIFIED_CHEQUE = "16";
  const PAYMENT_BANKERS_DRAFT = "17";
  const PAYMENT_CASH_ON_DELIVERY = "18";
  const PAYMENT_CARD = "19";

  const TAX_IVA = "01";
  const TAX_IPSI = "02";
  const TAX_IGIC = "03";
  const TAX_IRPF = "04";
  const TAX_OTHER = "05";
  const TAX_ITPAJD = "06";
  const TAX_IE = "07";
  const TAX_RA = "08";
  const TAX_IGTECM = "09";
  const TAX_IECDPCAC = "10";
  const TAX_IIIMAB = "11";
  const TAX_ICIO = "12";
  const TAX_IMVDN = "13";
  const TAX_IMSN = "14";
  const TAX_IMGSN = "15";
  const TAX_IMPN = "16";
  const TAX_REIVA = "17";
  const TAX_REIGIC = "18";
  const TAX_REIPSI = "19";
  const TAX_IPS = "20";
  const TAX_RLEA = "21";
  const TAX_IVPEE = "22";
  const TAX_IPCNG = "23";
  const TAX_IACNG = "24";
  const TAX_IDEC = "25";
  const TAX_ILTCAC = "26";
  const TAX_IGFEI = "27";
  const TAX_IRNR = "28";
  const TAX_ISS = "29";

  const UNIT_DEFAULT = "01";
  const UNIT_HOURS = "02";
  const UNIT_KILOGRAMS = "03";
  const UNIT_LITERS = "04";
  const UNIT_OTHER = "05";
  const UNIT_BOXES = "06";
  const UNIT_TRAYS = "07";
  const UNIT_BARRELS = "08";
  const UNIT_JERRICANS = "09";
  const UNIT_BAGS = "10";
  const UNIT_CARBOYS = "11";
  const UNIT_BOTTLES = "12";
  const UNIT_CANISTERS = "13";
  const UNIT_TETRABRIKS = "14";
  const UNIT_CENTILITERS = "15";
  const UNIT_CENTIMITERS = "16";
  const UNIT_BINS = "17";
  const UNIT_DOZENS = "18";
  const UNIT_CASES = "19";
  const UNIT_DEMIJOHNS = "20";
  const UNIT_GRAMS = "21";
  const UNIT_KILOMETERS = "22";
  const UNIT_CANS = "23";
  const UNIT_BUNCHES = "24";
  const UNIT_METERS = "25";
  const UNIT_MILIMETERS = "26";
  const UNIT_6PACKS = "27";
  const UNIT_PACKAGES = "28";
  const UNIT_PORTIONS = "29";
  const UNIT_ROLLS = "30";
  const UNIT_ENVELOPES = "31";
  const UNIT_TUBS = "32";
  const UNIT_CUBICMETERS = "33";
  const UNIT_SECONDS = "34";
  const UNIT_WATTS = "35";
  const UNIT_KWH = "36";

  protected static $SCHEMA_NS = array(
    self::SCHEMA_3_2   => "http://www.facturae.es/Facturae/2009/v3.2/Facturae",
    self::SCHEMA_3_2_1 => "http://www.facturae.es/Facturae/2014/v3.2.1/Facturae",
    self::SCHEMA_3_2_2 => "http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml"
  );
  protected static $DECIMALS = array(
    '' => [
      '' => ['min'=>2, 'max'=>2],
      // 'InvoiceTotal'                => ['min'=>2, 'max'=>8],
      // 'TotalGrossAmount'            => ['min'=>2, 'max'=>8],
      // 'TotalGrossAmountBeforeTaxes' => ['min'=>2, 'max'=>8],
      // 'TotalGeneralDiscounts'       => ['min'=>2, 'max'=>8],
      // 'TotalGeneralSurcharges'      => ['min'=>2, 'max'=>8],
      // 'TotalTaxOutputs'             => ['min'=>2, 'max'=>8],
      // 'TotalTaxesWithheld'          => ['min'=>2, 'max'=>8],
      'Tax/TaxRate'                    => ['min'=>2, 'max'=>8],
      // 'Tax/TaxableBase'                => ['min'=>2, 'max'=>8],
      // 'Tax/TaxAmount'                  => ['min'=>2, 'max'=>8],
      'Tax/EquivalenceSurchargeAmount' => ['min'=>2, 'max'=>8],
      'DiscountCharge/Rate'   => ['min'=>2, 'max'=>8],
      // 'DiscountCharge/Amount' => ['min'=>2, 'max'=>8],
      'Item/Quantity'            => ['min'=>0, 'max'=>8],
      'Item/UnitPriceWithoutTax' => ['min'=>2, 'max'=>8],
      // 'Item/TotalCost'           => ['min'=>2, 'max'=>8],
      // 'Item/GrossAmount'         => ['min'=>2, 'max'=>8],
    ],
    self::SCHEMA_3_2 => [
      '' => ['min'=>2, 'max'=>2],
      'DiscountCharge/Rate'   => ['min'=>4, 'max'=>4],
      'DiscountCharge/Amount' => ['min'=>6, 'max'=>6],
      'Item/Quantity'            => ['min'=>0, 'max'=>8],
      'Item/UnitPriceWithoutTax' => ['min'=>6, 'max'=>6],
      'Item/TotalCost'           => ['min'=>6, 'max'=>6],
      'Item/GrossAmount'         => ['min'=>6, 'max'=>6],
    ],
  );

  use PropertiesTrait;
  use UtilsTrait;
  use SignableTrait;
  use ExportableTrait;
}
