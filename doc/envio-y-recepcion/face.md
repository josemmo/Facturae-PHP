---
title: Comunicación con FACe
parent: Envío y recepción
nav_order: 1
permalink: /envio-y-recepcion/face.html
---

# Comunicación con FACe
Facturae-PHP permite establecer comunicación directa sin salir de la librería con los servicios web de [FACe](https://face.gob.es/) para, entre otros, remitir facturas electrónicas a la administración pública.

## Prerequisitos
Para poder usar los servicios web de FACe debes dar de alta el certificado expedido por la FNMT para tu persona física en [esta página web](https://face.gob.es/es/proveedores).

Una vez rellenados los datos de la empresa podrás empezar a usar FACe Web Services y deberás firmar toda comunicación al servicio **con la clave privada asociada al certificado que has dado de alta** (esta parte la hace Facturae-PHP automáticamente, solo hay que indicarle la ruta del banco de certificados).

---

## Cliente de FACe
El uso de FACe desde Facturae-PHP es extremadamente sencillo:

```php
use josemmo\Facturae\Face\FaceClient;

$face = new FaceClient("clave_publica.pem", "clave_privada.pem", "passphrase");
```

Al igual que al firmar una factura electrónica, puedes usar un solo archivo `.pfx` en vez de un par `.pem`:
```php
$face = new FaceClient("certificado.pfx", null, "passphrase");
```

Por defecto, `FaceClient` se comunica con el entorno de producción de FACe. Para usar el entorno de pruebas (*staging*) puedes utilizar el siguiente método:
```php
$face->setProduction(false);
```

Todas las llamadas a FACe desde `FaceClient` devuelven un objeto `SimpleXMLElement`. Consulta el [manual de PHP](http://php.net/manual/simplexml.examples-basic.php) para más información.

---

## Listado de métodos
A continuación se incluyen los métodos de `FaceClient` para llamar al servicio web FACe junto a una vista previa en JSON de la respuesta que devuelven.

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

### `$face->getAdministrations()`
Devuelve el listado de administraciones de primer nivel registradas en la plataforma.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "administraciones": {
        "administracion": [
            {
                "codigo": "E00003301",
                "nombre": "Ministerio De Defensa"
            },
            {
                "codigo": "A01002820",
                "nombre": "Junta De Andalucía"
            },
            [...]
        ]
    }
}
```

### `$face->getAdministrations(false)`
Devuelve el listado completo de todas las administraciones registradas en la plataforma.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "administraciones": {
        "administracion": [
            {
                "codigo": "L01069038",
                "nombre": "Ayuntamiento De Guadiana Del Caudillo"
            },
            {
                "codigo": "L07080004",
                "nombre": "àrea Metropolitana De Barcelona"
            },
            [...]
        ]
    }
}
```

### `$face->getUnits()`
Devuelve el listado completo de unidades (centros) registradas en la plataforma.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "relaciones": {
        "relacion": [
            {
                "organoGestor": {
                    "codigo": "EA0000118",
                    "nombre": "I.d. Instituto Social De Las Fuerzas Armadas"
                },
                "unidadTramitadora": {
                    "codigo": "EA0000118",
                    "nombre": "I.d. Instituto Social De Las Fuerzas Armadas"
                },
                "oficinaContable": {
                    "codigo": "EA0000118",
                    "nombre": "I.d. Instituto Social De Las Fuerzas Armadas"
                }
            },
            [...]
        ]
    }
}
```

### `$face->getUnits(:code)`
Devuelve el listado de unidades asociadas al código de la administración proporcionada.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "relaciones": {
        "relacion": [
            {
                "organoGestor": {
                    "codigo": "LA0000751",
                    "nombre": "Pleno"
                },
                "unidadTramitadora": {
                    "codigo": "LA0007418",
                    "nombre": "Secretaría General Del Pleno"
                },
                "oficinaContable": {
                    "codigo": "LA0008533",
                    "nombre": "I.d. En Coordinación Alcaldía, Portavoz, Cultura Y Deportes (i. D. En Coordinación Alcaldía, Portavoz, Cultura Y Deportes)"
                }
            },
            [...]
        ]
    }
}
```

### `$face->getNifs()`
Devuelve el listado completo de todos los NIFs registrados en la plataforma junto a información relativa.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "nifs": {
        "info": [
            {
                "organoGestor": {
                    "codigo": "P00000009",
                    "nombre": "UNIDAD DIR PRUEBAS 9"
                },
                "nif": "00000000T"
            },
            {
                "organoGestor": {
                    "codigo": "E04970101",
                    "nombre": "Agencia Española de Consumo, Seguridad Alimentaria y Nutricion"
                },
                "nif": "Q2801255G"
            },
            [...]
        ]
    }
}
```

### `$face->getNifs(:code)`
Devuelve el listado de NIFs asociados al código de la administración proporcionada.
```
{
    "resultado": {
        "codigo": "0",
        "descripcion": "Correcto",
        "codigoSeguimiento": {}
    },
    "nifs": {
        "info": [
            {
                "organoGestor": {
                    "codigo": "LA0000751",
                    "nombre": "Pleno"
                },
                "nif": "P2807900B"
            },
            {
                "organoGestor": {
                    "codigo": "LA0000756",
                    "nombre": "Área de Gobierno de Economía, Hacienda y Administración Pública"
                },
                "nif": "P2807900B"
            },
            [...]
        ]
    }
}
```

### `$face->sendInvoice(:email, :invoice, [:attachments])`
Envía una factura a FACe junto con unos adjuntos de forma opcional. Tanto la factura como los adjuntos deben ser una instancia válida de `FacturaeFile`.
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
Anula una factura previamente presentada a FACe al indicar su número de registro y el motivo de la anulación.
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
