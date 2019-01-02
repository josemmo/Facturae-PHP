---
title: Extensiones
nav_order: 6
has_children: true
permalink: /extensiones/
---

# Extensiones
Las [extensiones de formato](http://www.facturae.gob.es/formato/Paginas/extensiones-formato.aspx) son complementos que añaden funcionalidad al formato FacturaE. Facturae-PHP dispone de una interfaz propia para gestionar estas extensiones.

Una extensión puede ser accedida desde cualquier factura a través de `$fac->getExtension()` y cada extensión dispone de su propio set de métodos, memoria interna y funcionalidad.
