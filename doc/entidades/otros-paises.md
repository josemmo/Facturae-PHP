---
title: Otros países
parent: Entidades
nav_order: 5
permalink: /entidades/otros-paises.html
---

# Otros países
Por defecto, Facturae-PHP asume que las entidades residen en España.
Para establecer el código de país de una entidad, se usa la propiedad "countryCode":
```php
$entity = new FacturaeParty([
  "countryCode" => "FRA",
  "taxNumber"   => "12345678901",
  "name"        => "Una empresa de Francia",
  // [...]
]);
```

El valor del campo XML `<ResidenceTypeCode />` se calcula automáticamente en función del país de acuerdo a la especificación.
Es decir, toma los siguientes valores dependiendo del país:

- `R`: Para España
- `U`: Para países de la Unión Europea
- `E`: Resto de países

Se puede forzar que una entidad se considere (o no) de la Unión Europea usando la propiedad "isEuropeanUnionResident":
```php
$entity = new FacturaeParty([
  "isEuropeanUnionResident" => true,
  // [...]
]);
```
