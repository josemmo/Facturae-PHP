---
title: Datos del pago
parent: Propiedades de una factura
nav_order: 1
permalink: /propiedades/datos-del-pago.html
---

# Datos del pago

## Forma de pago
Es posible indicar la forma de pago de una factura. Por ejemplo, en caso de pagarse al contado:
```php
$fac->addPayment(new FacturaePayment([
  "method" => FacturaePayment::TYPE_CASH
]));
```

Los posibles valores que puede tomar este argumento se encuentra en la [tabla de constantes](../anexos/constantes.html#formas-de-pago) del anexo.

En caso de transferencia (entre otras formas de pago) también debe indicarse la cuenta bancaria destinataria:
```php
$fac->addPayment(new FacturaePayment([
  "method" => FacturaePayment::TYPE_TRANSFER,
  "iban"   => "ES7620770024003102575766"
]));
```

Si fuera necesario, se puede añadir el código BIC/SWIFT junto con el IBAN en el momento de establecer la forma de pago:
```php
$fac->addPayment(new FacturaePayment([
  "method" => FacturaePayment::TYPE_TRANSFER,
  "iban"   => "ES7620770024003102575766",
  "bic"    => "CAHMESMM"
]));
```

---

## Vencimiento
Por defecto, Facturae-PHP asume la fecha de emisión de la factura como la fecha de vencimiento de un pago.
Para establecer una fecha de vencimiento concreta, esta debe indicarse junto a los datos del pago:
```php
$fac->addPayment(new FacturaePayment([
  "method"  => FacturaePayment::TYPE_TRANSFER,
  "dueDate" => "2017-12-31",
  "iban"    => "ES7620770024003102575766",
  "bic"     => "CAHMESMM"
]));
```

---

## Múltiples vencimientos o formas de pago
La especificación de FacturaE permite establecer múltiples vencimientos en una misma factura.
Esto se consigue llamando varias veces al método `Facturae::addPayment()`:
```php
// Primer pago de 100,00 € al contado
// (fecha de vencimiento = fecha de emisión)
$fac->addPayment(new FacturaePayment([
  "method"  => FacturaePayment::TYPE_CASH,
  "amount"  => 100
]));

// Segundo pago de 199,90 € por transferencia bancaria
// (fecha de vencimiento el 31/12/2017)
$fac->addPayment(new FacturaePayment([
  "method"  => FacturaePayment::TYPE_TRANSFER,
  "amount"  => 199.90,
  "dueDate" => "2017-12-31",
  "iban"    => "ES7620770024003102575766",
  "bic"     => "CAHMESMM"
]));
```

---

## Periodo de facturación
Es posible establecer el periodo de facturación con el siguiente método:
```php
$fac->setBillingPeriod("2017-11-01", "2017-11-30");
```
