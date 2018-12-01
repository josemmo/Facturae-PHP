---
title: Uso sin Composer
parent: Ejemplos
nav_order: 2
permalink: /ejemplos/sin-composer.html
---

# Uso sin Composer
Este ejemplo muestra cómo usar `Facturae-PHP` sin tener configurado un entorno de Composer, solo descargando el código fuente de la librería.

```php
require_once 'ruta/hacia/Facturae-PHP/src/Facturae.php';
require_once 'ruta/hacia/Facturae-PHP/src/FacturaeCentre.php';
require_once 'ruta/hacia/Facturae-PHP/src/FacturaeItem.php';
require_once 'ruta/hacia/Facturae-PHP/src/FacturaeParty.php';

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

// Incluimos los datos del vendedor y del comprador (ver ejemplo sencillo)
$fac->setSeller(new FacturaeParty([...]));
$fac->setBuyer(new FacturaeParty([...]));

// Añadimos un producto de prueba
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
