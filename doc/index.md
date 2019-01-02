---
title: Inicio
nav_order: 1
permalink: /
---


# Integra la factura electrónica en tu empresa

Facturae-PHP es una solución integral para la creación, **firma** y envío automatizado a **FACe o FACeB2B** de facturas electrónicas siguiendo el formato estructurado [FacturaE](http://www.facturae.gob.es/) del Gobierno de España.
{: .fs-5 .fw-300 }

[Cómo empezar](#cómo-empezar){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 } [Ver proyecto en GitHub](https://github.com/josemmo/Facturae-PHP){: .btn .fs-5 }

---

## Características
- Generación de facturas 100% conformes con la [Ley 25/2013 del 27 de diciembre](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2013-13722)
- Exportación según las versiones de formato [3.2, 3.2.1 y 3.2.2](http://www.facturae.gob.es/formato/Paginas/version-3-2.aspx) de Facturae
- Firmado de acuerdo a la [política de firma de Facturae 3.1](http://www.facturae.gob.es/formato/Paginas/politicas-firma-electronica.aspx) basada en XAdES
- Sellado de tiempo según el [RFC3161](https://www.ietf.org/rfc/rfc3161.txt)
- Envío automatizado de facturas a **FACe y FACeB2B** 🔥

---

## Cómo empezar
### 1. Comprueba los requisitos
Aunque esta librería solo necesita **PHP 5.6 o superior** para poder crear facturas electrónicas, depende de las siguientes herramientas para otras funcionalidades:
 - **OpenSSL** para firmar facturas
 - **cURL** para el sellado de tiempo y conexión con FACe o FACeB2B
 - **libXML** para conexión con FACe o FACeB2B

### 2. Instala la librería
Facturae-PHP se distribuye como un paquete de Composer a través de Packagist:
```bash
$ composer require josemmo/facturae-php
```

Si prefiere no usar Composer, puedes clonar el repositorio en tu proyecto y añadir posteriormente los `require_once` necesarios a tu código.

### 3. Revisa la documentación
Consulta el apartado de [ejemplos](ejemplos/) para crear tu primera factura electrónica en dos minutos y consulta el resto de la documentación cuando estés listo para sacarle todo el partido a Facturae-PHP.

---

## Licencia
Facturae-PHP se distribuye bajo [licencia MIT](LICENSE). Eso implica que puedes utilizar este paquete en cualquier proyecto (incluso con fines comerciales), siempre y cuando hagas referencia al uso y autoría de la misma.
