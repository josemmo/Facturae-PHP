---
title: Precisión
parent: Propiedades de una factura
nav_order: 7
permalink: /propiedades/precision.html
---

# Precisión
Facturae-PHP ofrece dos formas distintas (modos de precisión) de calcular los totales de una factura.

Por defecto, el modo de precisión a utilizar es `Facturae::PRECISION_LINE` por compatibilidad con versiones anteriores
de la librería, aunque se puede cambiar llamando al siguiente método:
```php
$fac->setPrecision(Facturae::PRECISION_INVOICE);
```

## Precisión a nivel de línea
En este modo se prefiere que la suma de los totales de líneas de producto sea más precisa aunque como consecuencia cambien
los importes totales de la factura. Se corresponde con la constante `Facturae::PRECISION_LINE`.

Supongamos que tenemos una factura con las siguientes líneas de producto:

- 37,76 € de base imponible + IVA al 21%
- 26,80 € de base imponible + IVA al 21%
- 5,50 € de base imponible + IVA al 21%

Para esta configuración el total de la factura sería de **84,78 €**:

- 37,76 € × 1,21 = 45,6896 € ≈ 45,69 €
- 26,80 € × 1,21 = 32,428 € ≈ 32,43 €
- 5,50 € × 1,21 = 6,655 € ≈ 6,66 €

Total de la factura: 45,69 + 32,43 + 6,66 = 84,78 €

## Precisión a nivel de factura
Al contrario que en el modo anterior, esta precisión prefiere mantener el total de la factura lo más fiel posible a los
importes originales. Se corresponde con la constante `Facturae::PRECISION_INVOICE`.

Supongamos que tenemos una factura con las siguientes líneas de producto:

- 37,76 € de base imponible + IVA al 21%
- 26,80 € de base imponible + IVA al 21%
- 5,50 € de base imponible + IVA al 21%

Para esta configuración el total de la factura sería de **84,77 €**:

- 37,76 € × 1,21 = 45,6896 € (aunque en la factura se muestra 45,69 €)
- 26,80 € × 1,21 = 32,428 € (aunque en la factura se muestra 32,43 €)
- 5,50 € × 1,21 = 6,655 € (aunque en la factura se muestra 6,66 €)

Total de la factura: 45,6896 + 32,428 + 6,655 = 84,7726 € ≈ 84,77 €
