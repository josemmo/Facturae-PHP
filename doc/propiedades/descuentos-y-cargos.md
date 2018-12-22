---
title: Descuentos y cargos
parent: Propiedades de una factura
nav_order: 3
permalink: /propiedades/descuentos-y-cargos.html
---

# Descuentos y cargos
En esta página se explica cómo añadir **descuentos y cargos globales** que se aplican al total de la factura. Para añadir un descuento o cargo a una línea de producto consulta el subapartado [Descuentos y cargos](/productos/descuentos-y-cargos.html) dentro de la sección [Líneas de producto](/productos/).

Los dos métodos de una instancia `Facturae` para añadir descuentos y recargos a una factura son `$fac->addDiscount()` y `$fac->addCharge()` y se usan exactamente igual:
```php
// $fac->add[Discount/Charge]($reason, $value, $isPercentage=true)
$fac->addDiscount('A la mitad', 50);
$fac->addCharge('Recargo del 10%', 10);
```

Para especificar un importe concreto que se restará del importe bruto antes de impuestos en vez de un porcentaje, añade el flag `$isPercentage=false` como argumento adicional:
```php
$fac->addDiscount('5€ de descuento', 5, false);
```
