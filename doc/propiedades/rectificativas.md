---
title: Rectificativas
parent: Propiedades de una factura
nav_order: 9
permalink: /propiedades/rectificativas.html
---

# Facturas rectificativas
Por defecto, todos los documentos generados con la librería son facturas originales. Para generar una factura original
**rectificativa** se deben añadir una serie de propiedades adicionales a través del método `$fac->setCorrective()`:
```php
$fac->setCorrective(new CorrectiveDetails([
  // Serie y número de la factura a rectificar
  "invoiceSeriesCode" => "EMP201712",
  "invoiceNumber"     => "0002",

  // Código del motivo de la rectificación según:
  // - RD 1496/2003 (del "01" al 16")
  // - Art. 80 Ley 37/92 (del "80" al "85")
  "reason" => "01",

  // Periodo de tributación de la factura a rectificar
  "taxPeriodStart" => "2017-10-01",
  "taxPeriodEnd"   => "2017-10-31",

  // Modo del criterio empleado para la rectificación
  "correctionMethod" => CorrectiveDetails::METHOD_FULL
]));
```

Las razones (valores de `reason`) admitidas en la especificación de FacturaE son:

- `01`: Número de la factura
- `02`: Serie de la factura
- `03`: Fecha expedición
- `04`: Nombre y apellidos/Razón Social-Emisor
- `05`: Nombre y apellidos/Razón Social-Receptor
- `06`: Identificación fiscal Emisor/obligado
- `07`: Identificación fiscal Receptor
- `08`: Domicilio Emisor/Obligado
- `09`: Domicilio Receptor
- `10`: Detalle Operación
- `11`: Porcentaje impositivo a aplicar
- `12`: Cuota tributaria a aplicar
- `13`: Fecha/Periodo a aplicar
- `14`: Clase de factura
- `15`: Literales legales
- `16`: Base imponible
- `80`: Cálculo de cuotas repercutidas
- `81`: Cálculo de cuotas retenidas
- `82`: Base imponible modificada por devolución de envases / embalajes
- `83`: Base imponible modificada por descuentos y bonificaciones
- `84`: Base imponible modificada por resolución firme, judicial o administrativa
- `85`: Base imponible modificada cuotas repercutidas no satisfechas. Auto de declaración de concurso

Los distintos modos de rectificación (valores de `correctionMethod`) se definen en las [constantes del anexo](../anexos/constantes.html#modos-de-rectificación).
