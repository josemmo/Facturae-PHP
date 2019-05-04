---
title: Extensiones
nav_order: 6
has_children: true
permalink: /extensiones/
---

# Extensiones
Las [extensiones de formato](http://www.facturae.gob.es/formato/Paginas/extensiones-formato.aspx) son complementos que añaden funcionalidad al formato FacturaE. Similarmente, Facturae-PHP dispone de una interfaz propia para gestionar estas extensiones.

Una extensión puede ser accedida desde cualquier factura a través del método `$fac->getExtension()` y cada extensión dispone de su propio set de métodos, estado interno y funcionalidad.

## Extensiones de terceros
Además de las extensiones incluidas con Facturae-PHP, desde la versión 1.5.0 es posible utilizar clases externas como extensiones. Esto permite a las empresas **añadir lógica de negocio propia** a esta librería sin la obligación de compartir el código fuente bajo licencia MIT.

Para usar una extensión de terceros se deberá llamar al mismo método de antes con el nombre de la clase como parámetro:
```php
$awesome = $fac->getExtension(AwesomeExtension::class);
```

Una extensión de Facturae-PHP tiene un aspecto similar a este:
```php
class AwesomeExtension extends josemmo\Facturae\Extensions\FacturaeExtension {
  // NOTA: todos los métodos de este ejemplo son opcionales

  public function __getAdditionalData() {
    // Devuelve un string con el XML a inyectar en el bloque
    // "AdditionalData/Extensions" de un documento FacturaE.
  }

  public function __onBeforeExport() {
    // Lógica a ejecutar antes de exportar (generar el XML)
    // de una factura. Indicado para realizar acciones sobre
    // la instancia de la factura, que se puede obtener a
    // través del método `$this->getInvoice()`.
  }

  public function __onBeforeSign($xml) {
    // Lógica a ejecutar cuando el XML de la factura ya está
    // generado pero todavía no ha sido firmado.
    // Útil para modificar el XML antes de firmarlo, que
    // recibe por parámetro.
    return $xml;
  }

  public function __onAfterSign($xml) {
    // Lógica a ejecutar después de haber firmado el XML de la
    // factura y antes de ser devuelto por el método `export()`.
    // Útil para modificar el XML después de firmarlo, que
    // recibe por parámetro.
    return $xml;
  }

  // Otros métodos propios de la clase

}
```