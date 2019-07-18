---
title: Datos adicionales
parent: Propiedades de una factura
nav_order: 5
permalink: /propiedades/datos-adicionales.html
---

# Datos adicionales
Una factura puede contener una serie de datos adicionales, todos ellos opcionales, que se anexan al final de la misma.

## Documentos adjuntos
Ficheros en formato `xml`, `doc`, `gif`, `rtf`, `pdf`, `xls`, `jpg`, `bmp`, `tiff` o `html` que se adjuntan al documento XML de la factura.
```php
$fac->addAttachment(__DIR__ . '/adjunto.pdf', 'Descripción del documento (opcional)');
```

En vez de indicar la ruta del fichero adjunto, también se puede pasar una instancia de `FacturaeFile` a este método:
```php
$file = new FacturaeFile();
$file->loadFile(__DIR__ . '/adjunto.pdf');
$fac->addAttachment($file, 'Descripción del documento (opcional)');
```

---

## Factura relacionada
Indica el número de una factura relacionada con la instancia actual.
```php
$fac->setRelatedInvoice('AAA-BB-27317');
```

---

## Información adicional
Campo de texto libre de hasta 2500 caracteres que incluye la información que el emisor considere oportuno.
```php
$fac->setAdditionalInformation('En un lugar de la Mancha, de cuyo nombre no quiero acordarme, no ha mucho tiempo que vivía un hidalgo de los de lanza en astillero, adarga antigua, rocín flaco y galgo corredor.');
```