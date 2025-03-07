<?php
namespace josemmo\Facturae;

class CorrectiveDetails {
  const METHOD_FULL = "01";
  const METHOD_DIFFERENCES = "02";
  const METHOD_VOLUME_DISCOUNT = "03";
  const METHOD_AEAT_AUTHORIZED = "04";

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
   * Reason
   * @var string
   */
  public $reason = "01";

  /**
   * Reason description
   *
   * NOTE: Using a custom value might yield a non-compliant invoice.
   *
   * @var string|null
   */
  public $reasonDescription = null;

  /**
   * Start of tax period (as UNIX timestamp or parsable date string)
   * @var string|int|null
   */
  public $taxPeriodStart = null;

  /**
   * End of tax period (as UNIX timestamp or parsable date string)
   * @var string|int|null
   */
  public $taxPeriodEnd = null;

  /**
   * Correction method
   * @var string
   */
  public $correctionMethod = self::METHOD_FULL;

  /**
   * Correction method description
   *
   * NOTE: Using a custom value might yield a non-compliant invoice.
   *
   * @var string|null
   */
  public $correctionMethodDescription = null;

  /**
   * Free text to describe the reason why the invoice is corrected.
   * @var string|null
   */
  public $additionalReasonDescription = null;

  /**
   * Date on which the corrective invoice is issued. (as UNIX timestamp or parsable date string)  
   * Mandatory where "CorrectionMethod" takes the * value "01" or "02" 
   * @var string|int|null
   */
  public $invoiceIssueDate = null;

  /**
   * Class constructor
   * @param array $properties Corrective details properties as an array
   */
  public function __construct($properties=array()) {
    foreach ($properties as $key=>$value) {
      $this->{$key} = $value;
    }
  }

  /**
   * Get reason description
   * @return string Reason description
   */
  public function getReasonDescription() {
    // Use custom value if available
    if ($this->reasonDescription !== null) {
      return $this->reasonDescription;
    }

    // Fallback to default value per specification
    switch ($this->reason) {
      case "01":
        return "Número de la factura";
      case "02":
        return "Serie de la factura";
      case "03":
        return "Fecha expedición";
      case "04":
        return "Nombre y apellidos/Razón Social-Emisor";
      case "05":
        return "Nombre y apellidos/Razón Social-Receptor";
      case "06":
        return "Identificación fiscal Emisor/obligado";
      case "07":
        return "Identificación fiscal Receptor";
      case "08":
        return "Domicilio Emisor/Obligado";
      case "09":
        return "Domicilio Receptor";
      case "10":
        return "Detalle Operación";
      case "11":
        return "Porcentaje impositivo a aplicar";
      case "12":
        return "Cuota tributaria a aplicar";
      case "13":
        return "Fecha/Periodo a aplicar";
      case "14":
        return "Clase de factura";
      case "15":
        return "Literales legales";
      case "16":
        return "Base imponible";
      case "80":
        return "Cálculo de cuotas repercutidas";
      case "81":
        return "Cálculo de cuotas retenidas";
      case "82":
        return "Base imponible modificada por devolución de envases / embalajes";
      case "83":
        return "Base imponible modificada por descuentos y bonificaciones";
      case "84":
        return "Base imponible modificada por resolución firme, judicial o administrativa";
      case "85":
        return "Base imponible modificada cuotas repercutidas no satisfechas. Auto de declaración de concurso";
    }
    return "";
  }

  /**
   * Get correction method description
   * @return string Correction method description
   */
  public function getCorrectionMethodDescription() {
    // Use custom value if available
    if ($this->correctionMethodDescription !== null) {
      return $this->correctionMethodDescription;
    }

    // Fallback to default value per specification
    switch ($this->correctionMethod) {
      case self::METHOD_FULL:
        return "Rectificación íntegra";
      case self::METHOD_DIFFERENCES:
        return "Rectificación por diferencias";
      case self::METHOD_VOLUME_DISCOUNT:
        return "Rectificación por descuento por volumen de operaciones durante un periodo";
      case self::METHOD_AEAT_AUTHORIZED:
        return "Autorizadas por la Agencia Tributaria";
    }
    return "";
  }
}
