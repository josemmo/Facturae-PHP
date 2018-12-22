---
title: Referencias
parent: Propiedades de una factura
nav_order: 4
permalink: /propiedades/referencias.html
---

# Referencias
Con el fin de automatizar el flujo de trabajo que siguen las facturas electrónicas, tanto en envío como en recepción, desde la versión 3.2.2 del schema se pueden especificar identificadores que relacionen a la factura con otros documentos, empresas o personas.

```php
// NOTA: Solo para facturas según el schema 3.2.2 o superior
$fac->setReferences(
  "000298172",  // Código del expediente de contratación
  "BBBH-38272", // Referencia de pedido (ID de transacción)
  "BBBH-38271"  // Referencia del contrato del receptor
);
```
