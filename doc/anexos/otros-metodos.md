---
title: Otros métodos
parent: Anexos
nav_order: 1
permalink: /anexos/otros-metodos.html
---

# Otros métodos
Aquí se incluyen aquellos métodos del paquete que no se han podido catalogar en ninguno de los apartados anteriores.

## Totales de la factura
Es posible obtener un `array` con los totales de la factura:
```php
$totales = $fac->getTotals();
```

> #### NOTA
> El método `getTotals` se ha dejado público al considerar que puede ser de gran utilidad para muchos programadores.
>
> Aún así, este método es utilizado por la clase principal para generar el documento XML de la factura y, por tanto, **su salida está sujeta a cambios frecuentes** para acomodar futuras funciones.
>
> En conclusión: si usas este método en tu proyecto lee los cambios de versión antes de actualizar.
