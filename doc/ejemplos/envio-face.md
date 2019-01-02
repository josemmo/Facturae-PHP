---
title: Envío a FACe
parent: Ejemplos
nav_order: 3
permalink: /ejemplos/envio-face.html
---

# Envío a FACe
Este ejemplo muestra cómo enviar una factura a FACe sin usar otras dependencias externas.

> #### NOTA
> Si se quisiera enviar la factura a FACeB2B en vez de a FACe solo habría que cambiar todas las ocurrencias del término `FaceClient` por `FaceB2BClient`.

```php
require_once 'ruta/hacia/vendor/autoload.php';

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\Face\FaceClient;

// Creamos una factura válida (ver ejemplo simple)
$fac = new Facturae();
// [...]

// Cargamos la factura en una instancia de FacturaeFile
$invoice = new FacturaeFile();
$invoice->loadData($fac->export(), "test-invoice.xsig");

// Creamos una conexión con FACe
$face = new FaceClient("path_to_certificate.pfx", null, "passphrase");
//$face->setProduction(false); // Descomenta esta línea para entorno de desarrollo

// Subimos la factura a FACe
$res = $face->sendInvoice("email-de-notificaciones@email.com", $invoice);
if ($res->resultado->codigo == 0) {
  // La factura ha sido aceptada
  echo "Número de registro => " . $res->factura->numeroRegistro . "\n";
} else {
  // FACe ha rechazado la factura
}
```
