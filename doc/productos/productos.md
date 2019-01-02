---
title: Líneas de producto
nav_order: 3
has_children: true
permalink: /productos/
---

# Líneas de producto
La clase `FacturaeItem` representa a una línea de producto de una factura.

## Uso básico
Para añadir líneas de producto se utiliza el método `addItem` del objeto de la factura:
```php
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);
```

Es posible incluir una descripción además del concepto al añadir un producto al pasar un `array` de dos elementos en el primer parámetro del método:
```php
$fac->addItem(["Lámpara de pie", "Lámpara de madera de nogal con base rectangular y luz halógena"], 20.14, 3, Facturae::TAX_IVA, 21);
```

También se puede añadir un elemento sin impuestos, aunque solo debe hacerse en aquellos casos en los que se tenga total certeza:
```php
$fac->addItem("No llevo impuestos", 100, 1);
```

La forma adecuada de añadir elementos a los que no se les aplica IVA es la siguiente:
```php
$fac->addItem("Llevo IVA al 0%", 100, 1, Facturae::TAX_IVA, 0);
```

Nótese que Facturae-PHP no limita este tipo de comportamientos, es responsabilidad del usuario crear la factura de acuerdo a la legislación aplicable.
