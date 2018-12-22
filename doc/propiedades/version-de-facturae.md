---
title: Versión de FacturaE
parent: Propiedades de una factura
nav_order: 5
permalink: /propiedades/version-de-facturae.html
---

# Versión de FacturaE
Por defecto el paquete creará la factura siguiendo el formato (schema) FacturaE 3.2.1 al ser actualmente el más extendido. Si se quisiera utilizar otra versión se deberá indicar al instanciar el objeto de la factura:
```php
$fac = new Facturae(Facturae::SCHEMA_3_2_2);
```

Los posibles valores que puede tomar este argumento se encuentra en la [tabla de constantes](/anexos/constantes.html#formatos-de-facturae) del anexo.
