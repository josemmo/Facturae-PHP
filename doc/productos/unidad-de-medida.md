---
title: Unidad de medida
parent: Líneas de producto
nav_order: 2
permalink: /productos/unidad-de-medida.html
---

# Unidad de medida
Para especificar en qué unidad se encuentra la cantidad de una línea de producto se utiliza la propiedad `unitOfMeasure`:
```php
// Añadimos 20 litros de leche a 38 céntimos el litro
$fac->addItem(new FacturaeItem([
  "name" => "Leche entera",
  "quantity" => 20,
  "unitPrice" => 0.38,
  "unitOfMeasure" => Facturae::UNIT_LITERS,
  "taxes" => [Facturae::TAX_IVA => 10]
]));
```

Su valor por defecto (si no se indica) es `Facturae::UNIT_DEFAULT`, que es la unidad adimensional.

Dentro del anexo se encuentra [una tabla](/anexos/constantes.html#unidades-de-medida) con todos los posibles valores que puede tomar este campo.
