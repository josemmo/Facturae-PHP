---
title: Otros campos opcionales
parent: Líneas de producto
nav_order: 4
permalink: /productos/campos-opcionales.html
---

# Otros campos opcionales
Adicionalmente, una línea de producto puede tener declarados los siguientes atributos:
```php
$fac->addItem(new FacturaeItem([
  "description" => "Una descripción de hasta 2500 caracteres",
  "articleCode" => 1234,          // Código de artículo
  "fileReference" => "000298172", // Referencia del expediente
  "fileDate" => "2010-03-10",     // Fecha del expediente
  "sequenceNumber" => "1.0",      // Número de secuencia o línea del pedido

  // Campos relativos al contrato del emisor
  "issuerContractReference" => "A9938281",    // Referencia
  "issuerContractDate" => "2010-03-10",       // Fecha
  "issuerTransactionReference" => "A9938282", // Ref. de la operación, nº de pedido, contrato...
  "issuerTransactionDate" => "2010-03-10",    // Fecha de operación o de pedido

  // Campos relativos al contrator del receptor
  "receiverContractReference" => "BBBH-38271",    // Referencia
  "receiverContractDate" => "2010-03-10",         // Fecha
  "receiverTransactionReference" => "BBBH-38272", // Ref. de la operación, nº de pedido, contrato...
  "receiverTransactionDate" => "2010-03-10"       // Fecha de operación o de pedido
]));
```
