---
title: Descuentos y cargos
parent: Líneas de producto
nav_order: 3
permalink: /productos/descuentos-y-cargos.html
---

# Descuentos y cargos
Para añadir uno o varios descuentos a una línea de producto se debe indicar a la hora de crear la instancia de `FacturaeItem`. De forma similar, pueden añadirse cargos a un producto que, al contrario que los descuentos, incrementarán su precio.
```php
$fac->addItem(new FacturaeItem([
  "name" => "Un producto con descuento",
  "unitPriceWithoutTax" => 500, // NOTA: estos descuentos y cargos se
                                // aplican sobre la base imponible
  "discounts" => [
    ["reason" => "Descuento del 20%", "rate" => 20],
    ["reason" => "5€ de descuento", "amount" => 5]
  ],
  "charges" => [
    ["reason" => "Recargo del 1,30%", "rate" => 1.3]
  ],
  "taxes" => [Facturae::TAX_IVA => 21]
]));
```

## Descuentos y cargos sobre el total con impuestos
Supongamos que vendemos un producto por un importe de 100€ (IVA incluido) y descuento de 5€. Por defecto, Facturae-PHP aplicará el descuento sobre los 100€ **siempre y cuando se indique el campo `unitPrice` en vez de `unitPriceWithoutTax`**:
```php
$fac->addItem(new FacturaeItem([
  "name" => "Un producto con descuento",
  "unitPrice" => 100,
  "discounts" => [
    ["reason" => "Descuento de 5€ (IVA incluído)", "amount" => 5]
  ],
  "taxes" => [Facturae::TAX_IVA => 10]
]));
```

Esto significa que Facturae-PHP ajustará el importe del descuento para representarlo en función de la base imponible como especifica el estándar.

Si se quisiera evitar este comportamiento y aplicar un descuento a la base imponible de una línea de producto con impuestos incluídos, se deberá usar el flag `hasTaxes`:
```php
$fac->addItem(new FacturaeItem([
  "name" => "Un producto con descuento",
  "unitPrice" => 100,
  "discounts" => [
    ["reason" => "Descuento de 5€", "amount" => 5, "haxTaxes" => false]
  ],
  "taxes" => [Facturae::TAX_IVA => 10]
]));
```

> #### NOTA
> Consulta el test `DiscountsTest.php` dentro del directorio `tests/` para más información sobre este comportamiento.
