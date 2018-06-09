# Facturae-PHP
[![Travis](https://img.shields.io/travis/josemmo/Facturae-PHP/master.svg?style=flat-square)](https://travis-ci.org/josemmo/Facturae-PHP)
[![Codacy](https://img.shields.io/codacy/grade/cc00c08d95b247ae9e6f8f8366e87a04.svg?style=flat-square)](https://www.codacy.com/app/josemmo/Facturae-PHP)
[![Coverage](https://img.shields.io/codacy/coverage/cc00c08d95b247ae9e6f8f8366e87a04.svg?style=flat-square)](https://www.codacy.com/app/josemmo/Facturae-PHP)
[![Última versión](https://img.shields.io/packagist/v/josemmo/facturae-php.svg?style=flat-square)](https://packagist.org/packages/josemmo/facturae-php)
![Version de PHP](https://img.shields.io/packagist/php-v/josemmo/facturae-php.svg?style=flat-square)
![Tamaño](https://img.shields.io/github/languages/code-size/josemmo/Facturae-PHP.svg?style=flat-square)
![Licencia](https://img.shields.io/github/license/josemmo/Facturae-PHP.svg?style=flat-square)

Facturae-PHP es un paquete escrito puramente en PHP que permite generar facturas electrónicas siguiendo el formato estructurado [Facturae](http://www.facturae.gob.es/) e incluso añadirlas firma electrónica XAdES y sellado de tiempo sin necesidad de ninguna librería o clase adicional.

En apenas 25 líneas de código y con un tiempo de ejecución inferior a 0,4 µs es posible generar, firmar y exportar una factura electrónica totalmente válida:

```php
$fac = new Facturae();
$fac->setNumber('FAC201804', '123');
$fac->setIssueDate('2018-04-01');

$fac->setSeller(new FacturaeParty([
  "taxNumber" => "A00000000",
  "name"      => "Perico de los Palotes S.A.",
  "address"   => "C/ Falsa, 123",
  "postCode"  => "123456",
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
  "postCode"      => "654321",
  "town"          => "Madrid",
  "province"      => "Madrid"
]));

$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

$fac->sign("certificado.pfx", NULL, "passphrase");
$fac->export("mi-factura.xsig");
```

## Requisitos
 - PHP 5.6 o superior
 - OpenSSL (solo para firmar facturas)

## Características
- Generación de facturas 100% conformes con la [Ley 25/2013 del 27 de diciembre](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2013-13722) listas para enviar a FACe
- Exportación según las versiones [3.2, 3.2.1 y 3.2.2](http://www.facturae.gob.es/formato/Paginas/version-3-2.aspx) de Facturae
- Firmado de acuerdo a la [política de firma de Facturae 3.1](http://www.facturae.gob.es/formato/Paginas/politicas-firma-electronica.aspx) basada en XAdES
- Sellado de tiempo (TSA)

## Documentación
- [1. Introducción: instalación y uso básico](doc/01-introduccion.md)
- [2. Entidades: compradores, vendedores y centros](doc/02-entidades.md)
- [3. Líneas de producto](doc/03-lineas-de-producto.md)
- [4. Propiedades de una factura](doc/04-propiedades.md)
- [5. Firma electrónica y sellado de tiempo](doc/05-firma-electronica.md)
- [6. Extensión de FACeB2B](doc/06-faceb2b.md)
- [Otros métodos](doc/otros-metodos.md)
- [Anexo: tabla de constantes y validadores](doc/anexo.md)

## Licencia
Facturae-PHP se encuentra bajo [licencia MIT](LICENSE). Eso implica que puedes utilizar este paquete en cualquier proyecto (incluso con fines comerciales), siempre y cuando hagas referencia al uso y autoría de la misma.
