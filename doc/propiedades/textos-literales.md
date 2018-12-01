---
title: Textos literales
parent: Propiedades de una factura
nav_order: 2
permalink: /propiedades/textos-literales.html
---

# Textos literales
Se pueden incluir textos literales (generalmente, declaraciones responsables del proveedor) en el XML de la factura:
```php
$fac->addLegalLiteral("Este es un mensaje de prueba");
$fac->addLegalLiteral("Y este, otro más");
```

> #### NOTA
> El uso de `LegalLiterals` es obligatorio en determinadas facturas. Consulta la legislación vigente para más información.

---

## Descripción
Similar a la propiedad anterior, una factura puede contener un único campo de descripción para cualquier observación sobre la factura que carezca de valor legal:

```php
// NOTA: Solo para facturas según el schema 3.2.2 o superior
$fac->setDescription("Una descripción de la factura de hasta 2500 caracteres");
```
