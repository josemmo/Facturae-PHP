---
title: Cesionarios
parent: Entidades
nav_order: 3
permalink: /entidades/cesionarios.html
---

# Cesionarios
Un caso de uso contemplado en el formato FacturaE y admitido por FACe para tratar con entidades de la Administración Pública es la cesión de crédito, en las que se cede el crédito de una factura a una entidad tercera (el cesionario).

De acuerdo al [BOE-A-2019-13633](https://www.boe.es/eli/es/res/2019/09/17/(1)/con) se deberán incluir adicionalmente los datos del cesionario (representado dentro de Facturae-PHP como una instancia de `FacturaeParty`) y la cláusula de cesión:
```php
$fac->setAssignee(new FacturaeParty([
  "taxNumber" => "B00000000",
  "name"      => "Cesionario S.L.",
  "address"   => "C/ Falsa, 123",
  "postCode"  => "02001",
  "town"      => "Albacete",
  "province"  => "Albacete",
  "phone"     => "967000000",
  "fax"       => "967000001",
  "email"     => "cesionario@ejemplo.com"
]));
$fac->setAssignmentClauses('Cláusula de cesión');
```

Además, para cumplir con la especificación, es necesario establecer los datos relativos al pago tal y como se explica en [este apartado](../propiedades/datos-del-pago.md):
```php
$fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766", "CAHMESMM");
$fac->setDueDate("2017-12-31");
```
