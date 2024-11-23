---
title: Uso sin Composer
parent: Ejemplos
nav_order: 2
permalink: /ejemplos/sin-composer.html
---

# Uso sin Composer
Este ejemplo muestra cómo usar `Facturae-PHP` sin tener configurado un entorno de Composer, solo descargando el código fuente de la librería.

Para ello, se incluye el script "autoload.php" en el directorio raíz, que permite auto-cargar las clases de la librería.

```php
require_once 'ruta/hacia/Facturae-PHP/autoload.php'; // <-- Autoloader incluido con la librería

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
  "ruta/hacia/banco-de-certificados.p12",
  null,
  "passphrase"
);

// ... y exportarlo a un archivo
$fac->export("ruta/de/salida.xsig");
```
