---
title: Retenciones
parent: Propiedades de una factura
nav_order: 4
permalink: /propiedades/retenciones.html
---

# Retenciones
De forma similar a los descuentos y cargos globales, una factura puede contener retenciones sobre el **total a pagar** (tras aplicar impuestos).

El caso de uso más típico son las retenciones de garantía, un monto que se descuenta temporalmente para garantizar la calidad del producto o servicio facturado.

En una instancia `Facturae`, el método `$fac->addWithholding()` permite añadir estas retenciones:
```php
// $fac->addWithholding($reason, $value, $isPercentage=true)
$fac->addWithholding('Retención de garantía del 5%', 5);
```

Para especificar un importe concreto que se restará del **total a pagar** en vez de un porcentaje, añade el flag `$isPercentage=false` como argumento adicional:
```php
$fac->addWithholding('100€ de retención de garantía', 100, false);
```
