---
title: Múltiples impuestos
parent: Líneas de producto
nav_order: 1
permalink: /productos/multiples-impuestos.html
---

# Múltiples impuestos
Supongamos que se quieren añadir varios impuestos a una misma línea de producto. En este caso se deberá hacer uso de la API avanzada de productos de Facturae-PHP a través de la clase `FacturaeItem`:
```php
// Vamos a añadir un producto utilizando la API avanzada
// que tenga IVA al 10% e IRPF al 15%
$fac->addItem(new FacturaeItem([
  "name" => "Una línea con varios impuestos",
  "description" => "Esta línea es solo para probar Facturae-PHP",
  "quantity" => 1, // Esto es opcional, es el valor por defecto si se omite
  "unitPrice" => 43.64,
  "taxes" => [
    Facturae::TAX_IVA  => 10,
    Facturae::TAX_IRPF => 15
  ]
]));
```

Esta API también permite indicar el importe unitario del producto sin incluir impuestos (base imponible), ya que por defecto Facturae-PHP asume lo contrario:
```php
// Vamos a añadir 3 bombillas LED con un coste de 6,50 € ...
// ... pero con los impuestos NO INCLUÍDOS en el precio unitario
$fac->addItem(new FacturaeItem([
  "name" => "Bombilla LED",
  "quantity" => 3,
  "unitPriceWithoutTax" => 6.5, // NOTA: no confundir con unitPrice
  "taxes" => [Facturae::TAX_IVA => 21]
]));
```

Debe tenerse en cuenta que, por defecto, Facturae-PHP interprenta al IRPF como un impuesto retenido (aquellos que se restan a la base imponible) y al resto de impuestos como repercutidos (se suman a la base imponible).

Si necesitas crear una factura "especial" es posible sobreescribir el comportamiento por defecto a través de la propiedad `isWithheld`:
```php
// Para rizar un poco el rizo vamos a añadir una línea con IVA (repercutido)
// al 21% y también con impuestos especiales retenidos al 4%
$fac->addItem(new FacturaeItem([
  "name" => "Llevo impuestos retenidos",
  "quantity" => 1,
  "unitPrice" => 10,
  "taxes" => array(
    Facturae::TAX_IVA => 21,
    Facturae::TAX_IE  => ["rate"=>4, "isWithheld"=>true]
  )
]));
```
