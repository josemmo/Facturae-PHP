---
title: Suplidos
parent: Propiedades de una factura
nav_order: 8
permalink: /propiedades/suplidos.html
---

# Suplidos
La especificación de FacturaE permite añadir gastos a cuenta de terceros a una factura (suplidos).
Para ello, se debe hacer uso de la clase `ReimbursableExpense`:
```php
$fac->addReimbursableExpense(new ReimbursableExpense([
  "seller"            => new FacturaeParty(["taxNumber" => "00000000A"]),
  "buyer"             => new FacturaeParty(["taxNumber" => "12-3456789", "isEuropeanUnionResident" => false]),
  "issueDate"         => "2017-11-27",
  "invoiceNumber"     => "EX-19912",
  "invoiceSeriesCode" => "156A",
  "amount"            => 100.00
]));
```

Todos las propiedades de un suplido son opcionales excepto el importe (`amount`).
