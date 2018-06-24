# Comunicación con FACeB2B
> *Esta funcionalidad está en proceso de implementación. La documentación se irá completando en próximas versiones.*

## Cliente de FACeB2B
El uso de FACeB2B desde Facturae-PHP es extremadamente sencillo:

```php
use josemmo\Facturae\Face\FaceClientb2b;

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
> NOTA: Aunque en el Portal de Administración Electrónica se indica que ya está disponible el entorno de pruebas de FACeB2B, la realidad es que **el servicio web todavía no funciona**.

Todas las llamadas a FACeB2B devuelven un objeto `SimpleXMLElement` en `Faceb2bClient`. Consulta el [manual de PHP](http://php.net/manual/simplexml.examples-basic.php) para más información.

## Listado de métodos
A continuación se incluyen los métodos de `Faceb2bClient` para llamar al servicio web FACeB2B junto a una vista previa en JSON de la respuesta que devuelven.

### `$face->getStatus()`
Devuelve el listado de estados que puede tener una factura.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "estados": {
        "estado": [
            {
                "nombre": "Registrada",
                "codigo": "1200",
                "descripcion": "La factura ha sido registrada en el registro electrónico REC"
            },
            {
                "nombre": "Contabilizada la obligación reconocida",
                "codigo": "2400",
                "descripcion": "Contabilizada la obligación reconocida"
            },
            [...]
        ]
    }
}
```

### `$face->sendInvoice(:email, :invoice, [:attachments])`
Envía una factura a FACeB2B junto con unos adjuntos de forma opcional. Tanto la factura como los adjuntos deben ser una instancia válida de `FacturaeFile`.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "factura": {
        "numeroRegistro": "201800012345",
        "organoGestor": "P00000010",
        "unidadTramitadora": "P00000010",
        "oficinaContable": "P00000010",
        "identificadorEmisor": "A00000000",
        "numeroFactura": "123",
        "serieFactura": "FAC201804",
        "fechaRecepcion": "2018-06-16 11:17:30"
    }
}
```

### `$face->getInvoices(:regId)`
Devuelve los datos de una factura (si se proporciona un `string` con su número de registro) o varias (si se proporciona un `array` de números de registro).
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "factura": {
        "numeroRegistro": "201800012345",
        "tramitacion": {
            "codigo": "1200",
            "descripcion": "La factura ha sido registrada en el registro electrónico REC",
            "motivo": {}
        },
        "anulacion": {
            "codigo": "4100",
            "descripcion": "No solicitada anulación",
            "motivo": {}
        }
    }
}
```

### `$face->cancelInvoice(:regId, :reason)`
Anula una factura previamente presentada a FACeB2B al indicar su número de registro y el motivo de la anulación.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "factura": {
        "numeroRegistro": "201800012345",
        "mensaje": "Anulación solicitada correctamente"
    }
}
```
