# Facturae-PHP
[![CI](https://github.com/josemmo/Facturae-PHP/workflows/Tests/badge.svg)](https://github.com/josemmo/Facturae-PHP/actions)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/7c45d5ebdf9a4e88b0fe6b0e12d720b1)](https://www.codacy.com/gh/josemmo/Facturae-PHP)
[![Coverage](https://app.codacy.com/project/badge/Coverage/7c45d5ebdf9a4e88b0fe6b0e12d720b1)](https://www.codacy.com/gh/josemmo/Facturae-PHP)
[![Última versión estable](https://img.shields.io/packagist/v/josemmo/facturae-php)](https://packagist.org/packages/josemmo/facturae-php)
[![Versión de PHP](https://img.shields.io/badge/php-%3E%3D5.6%20%7C%7C%20%3E%3D7.0%20%7C%7C%20%3E%3D8.0-8892BF)](composer.json)
[![Documentación](https://img.shields.io/badge/docs-online-blue.svg?longCache=true)](https://josemmo.github.io/Facturae-PHP/)

Facturae-PHP es un paquete escrito puramente en PHP que permite generar facturas electrónicas siguiendo el formato estructurado [Facturae](http://www.facturae.gob.es/), **añadirlas firma electrónica** XAdES y sellado de tiempo, e incluso **enviarlas a FACe o FACeB2B** sin necesidad de ninguna librería o clase adicional.

En apenas 25 líneas de código y con un tiempo de ejecución inferior a 0,4 µs es posible generar, firmar y exportar una factura electrónica totalmente válida:

```php
$fac = new Facturae();
$fac->setNumber('FAC201804', '123');
$fac->setIssueDate('2018-04-01');

$fac->setSeller(new FacturaeParty([
  "taxNumber" => "A00000000",
  "name"      => "Perico de los Palotes S.A.",
  "address"   => "C/ Falsa, 123",
  "postCode"  => "12345",
  "town"      => "Madrid",
  "province"  => "Madrid"
]));
$fac->setBuyer(new FacturaeParty([
  "isLegalEntity" => false,
  "taxNumber"     => "00000000A",
  "name"          => "Antonio",
  "firstSurname"  => "García",
  "lastSurname"   => "Pérez",
  "address"       => "Avda. Mayor, 7",
  "postCode"      => "54321",
  "town"          => "Madrid",
  "province"      => "Madrid"
]));

$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

$fac->sign("certificado.pfx", null, "passphrase");
$fac->export("mi-factura.xsig");
```

También permite firmar facturas que hayan sido generadas con otro programa:

```php
$signer = new FacturaeSigner();
$signer->setSigningKey("certificado.pfx", null, "passphrase");

$xml = file_get_contents(__DIR__ . "/factura.xml");
$signedXml = $signer->sign($xml);
file_put_contents(__DIR__ . "/factura.xsig", $signedXml);
```

## Requisitos
  - PHP 5.6 o superior
  - OpenSSL (solo para firmar facturas)
  - cURL (solo para *timestamping* y FACe / FACeB2B)
  - libXML (solo para FACe y FACeB2B)

## Características
  - Generación de facturas 100% conformes con la [Ley 25/2013 del 27 de diciembre](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2013-13722)
  - Exportación según las versiones de formato [3.2, 3.2.1 y 3.2.2](http://www.facturae.gob.es/formato/Paginas/version-3-2.aspx) de Facturae
  - Firmado de acuerdo a la [política de firma de Facturae 3.1](http://www.facturae.gob.es/formato/Paginas/politicas-firma-electronica.aspx) basada en XAdES
  - Sellado de tiempo según el [RFC3161](https://www.ietf.org/rfc/rfc3161.txt)
  - Envío automatizado de facturas a **FACe y FACeB2B** 🔥

## Licencia
Facturae-PHP se encuentra bajo [licencia MIT](LICENSE). Eso implica que puedes utilizar este paquete en cualquier proyecto (incluso con fines comerciales), siempre y cuando hagas referencia al uso y autoría de la misma.
