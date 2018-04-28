# Entidades

## Compradores y vendedores
Los compradores y vendedores son representados en Facturae-PHP con la clase `FacturaeParty` y pueden contener los siguientes atributos:
```php
$empresa = new FacturaeParty([
  "isLegalEntity" => true, // Se asume true si se omite
  "taxNumber"     => "A00000000",
  "name"          => "Perico el de los Palotes S.A.",
  "address"       => "C/ Falsa, 123",
  "postCode"      => "123456",
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
  "postCode"      => "654321",
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

## Centros administrativos
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
