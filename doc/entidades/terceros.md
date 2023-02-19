---
title: Terceros
parent: Entidades
nav_order: 4
permalink: /entidades/terceros.html
---

# Terceros
Un tercero o *Third-Party* es la entidad que genera y firma una factura cuando esta no coincide con el emisor.
Por ejemplo, en el caso de una gestoría que trabaja con varios clientes y emite las facturas en su nombre.

En el caso de Facturae-PHP, pueden especificarse los datos de un tercero de la siguiente forma:
```php
$fac->setThirdParty(new FacturaeParty([
  "taxNumber" => "B99999999",
  "name"      => "Gestoría de Ejemplo, S.L.",
  "address"   => "C/ de la Gestoría, 24",
  "postCode"  => "23456",
  "town"      => "Madrid",
  "province"  => "Madrid",
  "phone"     => "915555555",
  "email"     => "noexiste@gestoria.com"
]));
```
