---
title: Centros administrativos
parent: Entidades
nav_order: 2
permalink: /entidades/centros-administrativos.html
---

# Centros administrativos
Para poder emitir facturas a organismos públicos a través de FACe es necesario indicar los centros administrativos relacionados con la administración a facturar.
Estos datos pueden obtenerse a través del [directorio de organismos de FACe](https://face.gob.es/es/directorio/administraciones/) y deben ser indicados a Facturae-PHP de la siguiente forma:
```php
// Tomamos como ejemplo el Ayuntamiento de San Sebastián de los Reyes
$ayto = new FacturaeParty([
  "taxNumber" => "P2813400E",
  "name"      => "Ayuntamiento de San Sebastián de los Reyes",
  "address"   => "Plaza de la Constitución, 1",
  "postCode"  => "28701",
  "town"      => "San Sebastián de los Reyes",
  "province"  => "Madrid",
  "centres"   => [
    new FacturaeCentre([
      "role"     => FacturaeCentre::ROLE_GESTOR,
      "code"     => "L01281343",
      "name"     => "Intervención Municipal",
      "address"  => "Plaza de la Constitución, 1",
      "postCode" => "28701",
      "town"     => "San Sebastián de los Reyes",
      "province" => "Madrid"
    ]),
    new FacturaeCentre([
      "role"     => FacturaeCentre::ROLE_TRAMITADOR,
      "code"     => "L01281343",
      "name"     => "Intervención Municipal",
      "address"  => "Plaza de la Constitución, 1",
      "postCode" => "28701",
      "town"     => "San Sebastián de los Reyes",
      "province" => "Madrid"
    ]),
    new FacturaeCentre([
      "role"     => FacturaeCentre::ROLE_CONTABLE,
      "code"     => "L01281343",
      "name"     => "Intervención Municipal",
      "address"  => "Plaza de la Constitución, 1",
      "postCode" => "28701",
      "town"     => "San Sebastián de los Reyes",
      "province" => "Madrid"
    ])
  ]
]);
```

---

> #### NOTA
> Los campos de dirección (`address`, `postCode`, `town`, `province` y `countryCode`) son opcionales en `FacturaeCentre` y si se omiten se utilizarán los introducidos en su entidad padre de `FacturaeParty`.
