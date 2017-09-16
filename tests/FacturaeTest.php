<?php

require_once __DIR__ . '/../vendor/autoload.php';

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
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
// precio unitario de 20,14€, IVA al 21% YA INCLUÍDO
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

// Y ahora, una línea con IVA al 0%
$fac->addItem("Algo exento de IVA", 100, 1, Facturae::TAX_IVA, 0);

// Vamos a añadir un producto utilizando la API avanzada
// que tenga IVA al 10% e IRPF al 15%
$fac->addItem(new FacturaeItem([
  "name" => "Una línea con varios impuestos",
  "description" => "Esta línea es solo para probar Facturae-PHP",
  "quantity" => 1, // Esto es opcional, es el valor por defecto si se omite
  "unitPrice" => 43.64,
  "taxes" => array(
    Facturae::TAX_IVA  => 10,
    Facturae::TAX_IRPF => 15
  )
]));

// Para terminar, añadimos 3 bombillas LED con un coste de 6,50 € ...
// ... pero con los impuestos NO INCLUÍDOS en el precio unitario
$fac->addItem(new FacturaeItem([
  "name" => "Bombilla LED",
  "quantity" => 3,
  "unitPriceWithoutTax" => 6.5, // NOTA: no confundir con unitPrice
  "taxes" => array(Facturae::TAX_IVA => 21)
]));

// Ya solo queda firmar la factura ...
$fac->sign(__DIR__ . "/public.pem", __DIR__ . "/private.pem", "12345");

// ... y exportarlo a un archivo
$fac->export(__DIR__ . "/salida.xsig");
