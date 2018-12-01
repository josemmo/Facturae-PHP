---
title: Compradores y vendedores
parent: Entidades
nav_order: 1
permalink: /entidades/compradores-y-vendedores.html
---

# Compradores y vendedores
Los compradores y vendedores son representados en Facturae-PHP con la clase `FacturaeParty` y pueden contener los siguientes atributos:
```php
$empresa = new FacturaeParty([
  "isLegalEntity" => true, // Se asume true si se omite
  "taxNumber"     => "A00000000",
  "name"          => "Perico el de los Palotes S.A.",
  "address"       => "C/ Falsa, 123",
  "postCode"      => "12345",
  "town"          => "Madrid",
  "province"      => "Madrid",
  "countryCode"   => "ESP",  // Se asume España si se omite
  "book"             => "0",  // Libro
  "merchantRegister" => "RG", // Registro Mercantil
  "sheet"            => "1",  // Hoja
  "folio"            => "2",  // Folio
  "section"          => "3",  // Sección
  "volume"           => "4",  // Tomo
  "email"   => "contacto@perico.com"
  "phone"   => "910555444",
  "fax"     => "910555443"
  "website" => "http://www.perico.com/",
  "contactPeople" => "Perico",
  "cnoCnae" => "4791", // Clasif. Nacional de Act. Económicas
  "ineTownCode" => "280796" // Cód. de municipio del INE
]);

$personaFisica = new FacturaeParty([
  "isLegalEntity" => false,
  "taxNumber"     => "00000000A",
  "name"          => "Antonio",
  "firstSurname"  => "García",
  "lastSurname"   => "Pérez",
  "address"       => "Avda. Mayor, 7",
  "postCode"      => "54321",
  "town"          => "Madrid",
  "province"      => "Madrid",
  "countryCode"   => "ESP",  // Se asume España si se omite
  "email"   => "antonio@email.com"
  "phone"   => "910777888",
  "fax"     => "910777888"
  "website" => "http://www.antoniogarcia.es/",
  "contactPeople" => "Antonio García",
  "cnoCnae" => "4791", // Clasif. Nacional de Act. Económicas
  "ineTownCode" => "280796" // Cód. de municipio del INE
]);
```
