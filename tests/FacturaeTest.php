<?php 

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

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
  "name"      => "Perico el de los Palotes S.A.",
  "address"   => "C/ Falsa, 123",
  "postCode"  => "23456",
  "town"      => "Madrid",
  "province"  => "Madrid"
]));

// Incluimos los datos del comprador
// Con finos demostrativos el comprador será
// una persona física en vez de una empresa
$fac->setBuyer(new FacturaeParty([
  "isLegalEntity" => false,       // Importante!
  "taxNumber"     => "00000000A",
  "name"          => "Antonio",
  "firstSurname"  => "García",
  "lastSurname"   => "Pérez",
  "address"       => "Avda. Mayor, 7",
  "postCode"      => "65432",
  "town"          => "Madrid",
  "province"      => "Madrid"
]));

// Añadimos los productos a incluir en la factura
// En este caso, probaremos con tres lámpara por
// precio unitario de 20,14€ + 21% IVA
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

// Ya solo queda firmar la factura ...
$fac->sign(__DIR__ . "/public.pem",
  __DIR__ . "/private.pem", "12345");

// ... y exportarlo a un archivo
$fac->export(__DIR__ . "/salida.xsig");
