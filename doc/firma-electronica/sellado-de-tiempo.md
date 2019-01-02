---
title: Sellado de tiempo
parent: Firma electrónica
nav_order: 1
permalink: /firma-electronica/sellado-de-tiempo.html
---

# Sellado de tiempo
Además de firmar las facturas, se puede añadir un sello de tiempo según el [RFC 3161](https://tools.ietf.org/html/rfc3161) para garantizar su validez durante unos meses o incluso años. Para ello, se debe llamar al siguiente método antes de exportar la factura:
```php
$fac->setTimestampServer("https://freetsa.org/tsr");
```

En caso de necesitar autenticarse con el servidor TSA se deben pasar el usuario y contraseña como parámetros:
```php
$fac->setTimestampServer("https://www.safestamper.com/tsa", "usuario", "contraseña");
```
