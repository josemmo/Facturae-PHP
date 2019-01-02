---
title: Comunicación con FACeB2B
parent: Envío y recepción
nav_order: 2
permalink: /envio-y-recepcion/faceb2b.html
---

# Comunicación con FACeB2B
Facturae-PHP permite establecer comunicación directa sin salir de la librería con los servicios web de [FACeB2B](https://faceb2b.gob.es/) para enviar y recibir facturas entre empresas del sector privado.

## Prerequisitos
Para poder usar los servicios web de FACeB2B el proceso es un poco más complicado que con FACe, ya que se requiere de varios pasos:
1. Da de alta tu empresa en [DIRe](https://dire.gob.es) usando el certificado expedido por la FNMT para tu persona física. **Asegúrate de que activas la empresa.**
2. A continuación, asocia tu empresa con [FACeB2B](https://faceb2b.gob.es) autenticándote como "Cliente" con el mismo certificado de antes.
3. También desde FACeB2B, registra tu empresa como ESF. **No hace falta** que marques la casilla de gestionar facturas de terceros.
4. Crea una nueva plataforma de facturación y sube la **clave pública** del certificado que utilizarás para comunicarte con los Web Services. Puede ser la clave pública del certificado que hemos estado usando hasta ahora.
5. Asocia las unidades DIRe desde FACeB2B (pestaña "Alta de clientes") a la plataforma que acabas de crear.

---

## Cliente de FACeB2B
El uso de FACeB2B desde Facturae-PHP es extremadamente sencillo:

```php
use josemmo\Facturae\Face\Faceb2bClient;

$face = new Faceb2bClient("clave_publica.pem", "clave_privada.pem", "passphrase");
```

Al igual que al firmar una factura electrónica, puedes usar un solo archivo `.pfx` en vez de un par `.pem`:
```php
$face = new Faceb2bClient("certificado.pfx", null, "passphrase");
```

Por defecto, `Faceb2bClient` se comunica con el entorno de producción de FACeB2B. Para usar el entorno de pruebas (*staging*) puedes utilizar el siguiente método:
```php
$face->setProduction(false);
```

Todas las llamadas a FACeB2B desde `Faceb2bClient` devuelven un objeto `SimpleXMLElement`. Consulta el [manual de PHP](http://php.net/manual/simplexml.examples-basic.php) para más información.

---

## Listado de métodos
A continuación se incluyen los métodos de `Faceb2bClient` para llamar al servicio web FACeB2B junto a una vista previa en JSON de la respuesta que devuelven.

### `$face->sendInvoice(:invoice, [:attachment])`
Envía una factura válida a FACeB2B y un archivo adjunto (solo uno, a diferencia de FACe que admite varios) de forma opcional. Tanto la factura como el adjunto deben ser una instancia válida de `FacturaeFile`.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "invoiceDetail": {
        "registryNumber": "201801122333",
        "invoiceNumber": "123",
        "invoiceSeriesCode": "FAC201804",
        "receivingUnit": {
            "code": "ESB999999990000",
            "name": "Nombre de la unidad"
        },
        "additionalAdministrativeCentres": {},
        "sellerTaxIdentification": "A00000000",
        "amount": "1.00",
        "currency": "EUR",
        "issueDate": "2018-06-17T00:00:00+02:00",
        "receptionDate": "2018-07-04T19:41:03+02:00",
        "statusInfo": {
            "status": {
                "code": "1200",
                "name": "Registrada",
                "description": "Registrada"
            },
            "modificationDate": "2018-07-04T19:41:10+02:00"
        },
        "cancellationInfo": {}
    }
}
```

### `$face->getInvoiceDetails(:regId)`
Devuelve los datos de una factura a partir de su número de registro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "invoiceDetail": {
        "registryNumber": "201801122333",
        "invoiceNumber": "123",
        "invoiceSeriesCode": "FAC201804",
        "receivingUnit": {
            "code": "ESB999999990000",
            "name": "Nombre de la unidad"
        },
        "additionalAdministrativeCentres": {},
        "sellerTaxIdentification": "A00000000",
        "amount": "1.00",
        "currency": "EUR",
        "issueDate": "2018-06-17T00:00:00+02:00",
        "receptionDate": "2018-07-04T19:41:03+02:00",
        "statusInfo": {
            "status": {
                "code": "1200",
                "name": "Registrada",
                "description": "Registrada"
            },
            "modificationDate": "2018-07-04T19:41:10+02:00"
        },
        "cancellationInfo": {}
    }
}
```

### `$face->requestInvoiceCancellation(:regId, :reason, [:comment])`
Solicita la cancelación de una factura a partir de su número de registro, debiendo indicarse un motivo para la cancelación (generalmente `C001`) y opcionalmente un comentario (cadena de texto).
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->getRegisteredInvoices([:receivingUnit])`
Devuelve el listado de facturas (hasta un máximo de 500) registradas en FACeB2B gestionadas por la ESF asociada al certificado usado en las comunicaciones con el Web Service. Opcionalmente se puede indicar el código de una unidad receptora para filtrar facturas.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "newRegisteredInvoices": {
        "registryNumber": "201801122333"
    }
}
```

### `$face->getInvoiceCancellations()`
Devuelve el listado de facturas canceladas gestionadas por la ESF asociada al certificado usado en las comunicaciones con el Web Service.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "invoiceCancellationRequests": {
        "registryNumber": "201801122333"
    }
}
```

### `$face->downloadInvoice(:regId, [:validate])`
Devuelve los datos de una factura y el archivo original codificado en Base64 a partir de su número de registro. Por defecto también se devuelve un informe (`reportFile`) con la validez de la misma, salvo que se pase `false` como valor del segundo parámetro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "invoiceDetail": {
        "registryNumber": "201801122333",
        "invoiceNumber": "123",
        "invoiceSeriesCode": "FAC201804",
        "receivingUnit": {
            "code": "ESB999999990000",
            "name": "Nombre de la unidad"
        },
        "additionalAdministrativeCentres": {},
        "sellerTaxIdentification": "A00000000",
        "amount": "1.00",
        "currency": "EUR",
        "issueDate": "2018-06-17T00:00:00+02:00",
        "receptionDate": "2018-07-04T19:41:03+02:00",
        "statusInfo": {
            "status": {
                "code": "1200",
                "name": "Registrada",
                "description": "Registrada"
            },
            "modificationDate": "2018-07-04T19:41:10+02:00"
        },
        "cancellationInfo": {}
    },
    "invoiceFile": {
        "content": "PD94bWwg...cmFlPg==",
        "name": "factura-de-prueba.xsig",
        "mime": "text/xml"
    },
    "reportFile": {
        "content": "PD94bWwg...cnQ+Cg==",
        "name": "201801122333.xml",
        "mime": "text/xml"
    }
}
```

### `$face->confirmInvoiceDownload(:regId)`
Notifica a FACeB2B de la descarga de una factura a partir de su número de registro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->rejectInvoice(:regId, :reason, [:comment])`
Rechaza la recepción de una factura a partir de su número de registro, debiendo indicarse un motivo para el rechazo (generalmente `R001`) y opcionalmente un comentario (cadena de texto).
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->markInvoiceAsPaid(:regId)`
Marca una factura como pagada a partir de su número de registro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->acceptInvoiceCancellation(:regId)`
Acepta la cancelación de una factura a partir de su número de registro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->rejectInvoiceCancellation(:regId, :comment)`
Acepta la cancelación de una factura a partir de su número de registro. Debe indicarse un comentario sobre el motivo del rechazo de la petición de cancelación.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    }
}
```

### `$face->validateInvoiceSignature(:regId, :invoice)`
Valida la autenticidad de una factura a partir de su número de registro y de su firma en XML. Este método se utiliza para que terceros puedan comprobar la autoría e integridad de una factura sin disponer de los contenidos de la misma, solo teniendo su firma XAdES en XML y su ID de registro.
```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "reportFile": {
        "content": "PD94bWwg...cnQ+Cg==",
        "name": "201801122333.xml",
        "mime": "text/xml"
    }
}
```

### `$face->getCodes([:type])`
Devuelve el listado de códigos de negocio que emplea el Web Service. Opcionalmente se pueden obtener solo los códigos de un determinado tipo.
Los tipos válidos (según la documentación oficial de FACeB2B) son:
- `invoiceStatus`
- `rejectionReason`
- `cancellationReason`

```
{
    "resultStatus": {
        "code": "0",
        "message": "Success",
        "detail": {},
        "trackingCode": {}
    },
    "codes": {
        "item": [
            {
                "code": "1200",
                "name": "Registrada en broker FACeB2B",
                "description": "La factura ha sido registrada en el registro electrónico REC"
            },
            [...]
        ]
    }
}
```
