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
$fac->setPaymentMethod(Facturae::PAYMENT_CASH);
```

Los posibles valores que puede tomar este argumento se encuentra en la [tabla de constantes](../anexos/constantes.html#formas-de-pago) del anexo.

En caso de transferencia (entre otras formas de pago) también debe indicarse la cuenta bancaria destinataria:
```php
$fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766");
```

Si fuera necesario, se puede añadir el código BIC/SWIFT junto con el IBAN en el momento de establecer la forma de pago:
```php
$fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766", "CAHMESMM");
```

---

## Vencimiento
Para establecer la fecha de vencimiento del pago:
```php
$fac->setDueDate("2017-12-31");
```

> #### NOTA
> Por defecto, si se establece una forma de pago y no se indica la fecha de vencimiento se interpreta la fecha de la factura como tal.

---

## Periodo de facturación
Es posible establecer el periodo de facturación con el siguiente método:
```php
$fac->setBillingPeriod("2017-11-01", "2017-11-30");
```
