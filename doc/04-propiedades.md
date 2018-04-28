# Propiedades de una factura

## Forma de pago y vencimiento
Es posible indicar la forma de pago de una factura. Por ejemplo, en caso de pagarse al contado:
```php
$fac->setPaymentMethod(Facturae::PAYMENT_CASH);
```

En caso de transferencia también debe indicarse la cuenta bancaria destinataria:
```php
$fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766");
```

Para establecer la fecha de vencimiento del pago:
```php
$fac->setDueDate("2017-12-31");
```

> #### NOTA
> Por defecto, si se establece una forma de pago y no se indica la fecha de vencimiento se interpreta la fecha de la factura como tal.

## Periodo de facturación
Es posible establecer el periodo de facturación con el siguiente método:
```php
$fac->setBillingPeriod("2017-11-01", "2017-11-30");
```

## Textos literales
Se pueden incluir textos literales (generalmente, declaraciones responsables del proveedor) en el XML de la factura:
```php
$fac->addLegalLiteral("Este es un mensaje de prueba");
$fac->addLegalLiteral("Y este, otro más");
```

> #### NOTA
> El uso de `LegalLiterals` es obligatorio en determinadas facturas. Consulta la legislación vigente para más información.
