---
title: Envío a FACeB2B
parent: Ejemplos
nav_order: 4
permalink: /ejemplos/envio-faceb2b.html
---

# Envío a FACeB2B
Este ejemplo muestra cómo enviar una factura a FACeB2B sin usar otras dependencias externas.

```php
require_once 'ruta/hacia/vendor/autoload.php';

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\Face\Faceb2bClient;

// Creamos una factura válida (ver ejemplo simple)
$fac = new Facturae();
// [...]

// Cargamos la factura en una instancia de FacturaeFile
$invoice = new FacturaeFile();
$invoice->loadData($fac->export(), "test-invoice.xsig");

// Creamos una conexión con FACe
$faceb2b = new Faceb2bClient("path_to_certificate.pfx", null, "passphrase");
//$faceb2b->setProduction(false); // Descomenta esta línea para entorno de desarrollo

// Subimos la factura a FACeB2B
$res = $faceb2b->sendInvoice($invoice);
if ($res->resultStatus->code == 0) {
  // La factura ha sido aceptada
  echo "Número de registro => " . $res->invoiceDetail->registryNumber . "\n";
} else {
  // FACeB2B ha rechazado la factura
}
```
