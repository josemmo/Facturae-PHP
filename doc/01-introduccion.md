# Introducción
Facturae-PHP pretende ser una clase extremadamente rápida y sencilla de usar. A continuación se incluyen varios ejemplos sobre su utilización.
Para más información sobre todos los métodos de Facturae-PHP, la clase se encuentra comentada según bloques de código de [phpDocumentor](https://www.phpdoc.org/).

## Instalación
Facturae-PHP se instala como cualquier otro paquete de Composer, ya sea modificando las dependencias del archivo `composer.json` de tu proyecto o mediante línea de comandos:

```
composer require josemmo/facturae-php
```

## Ejemplo básico usando Composer
```php
require_once __DIR__ . '/../vendor/autoload.php';

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeParty;

// Creamos la factura
$fac = new Facturae();

// Asignamos el número EMP2017120003 a la factura
// Nótese que Facturae debe recibir el lote y el
// número separados
$fac->setNumber('EMP201712', '0003');

// Asignamos el 01/12/2017 como fecha de la factura
$fac->setIssueDate('2017-12-01');

// Incluimos los datos del vendedor
$fac->setSeller(new FacturaeParty([
  "taxNumber" => "A00000000",
  "name"      => "Perico de los Palotes S.A.",
  "address"   => "C/ Falsa, 123",
  "postCode"  => "123456",
  "town"      => "Madrid",
  "province"  => "Madrid"
]));

// Incluimos los datos del comprador,
// con finos demostrativos el comprador será
// una persona física en vez de una empresa
$fac->setBuyer(new FacturaeParty([
  "isLegalEntity" => false,       // Importante!
  "taxNumber"     => "00000000A",
  "name"          => "Antonio",
  "firstSurname"  => "García",
  "lastSurname"   => "Pérez",
  "address"       => "Avda. Mayor, 7",
  "postCode"      => "654321",
  "town"          => "Madrid",
  "province"      => "Madrid"
]));

// Añadimos los productos a incluir en la factura
// En este caso, probaremos con tres lámpara por
// precio unitario de 20,14€ con 21% de IVA ya incluído
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

// Ya solo queda firmar la factura ...
$fac->sign(
  "ruta/hacia/clave_publica.pem",
  "ruta/hacia/clave_privada.pem",
  "passphrase"
);

// ... y exportarlo a un archivo
$fac->export("ruta/de/salida.xsig");
```

> #### NOTA
> En caso de no utilizar Composer, se deberá sustituir en el ejemplo anterior la línea de código:
> ```php
> require_once __DIR__ . '/../vendor/autoload.php';
> ```
> Por las siguientes:
> ```php
> require_once "ruta/hacia/Facturae-PHP/src/Facturae.php";
> require_once "ruta/hacia/Facturae-PHP/src/FacturaeCentre.php";
> require_once "ruta/hacia/Facturae-PHP/src/FacturaeItem.php";
> require_once "ruta/hacia/Facturae-PHP/src/FacturaeParty.php";
> ```

## Versión de Facturae
Por defecto el paquete creará la factura siguiendo el formato Facturae 3.2.1 al ser actualmente el más extendido. Si se quisiera utilizar otra versión se deberá indicar al instanciar el objeto de la factura:
```php
$fac = new Facturae(Facturae::SCHEMA_3_2_2);
```
