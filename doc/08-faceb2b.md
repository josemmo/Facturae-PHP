# Comunicación con FACeB2B
> *Esta funcionalidad está en proceso de implementación. La documentación estará disponible en próximas versiones.*

Facturae-PHP permite establecer comunicación directa sin salir de la librería con los servicios web de [FACeB2B](https://faceb2b.gob.es/) para enviar y recibir facturas entre empresas del sector privado.

## Prerequisitos
Para poder usar los servicios web de FACeB2B el proceso es un poco más complicado que con FACe, ya que se requiere de varios pasos:
1. Da de alta tu empresa en [DIRe](https://dire.gob.es) usando el certificado expedido por la FNMT para tu persona física. **Asegúrate de que activas la empresa.**
2. A continuación, asocia tu empresa con [FACeB2B](https://faceb2b.gob.es) autenticándote como "Cliente" con el mismo certificado de antes.
3. También desde FACeB2B, registra tu empresa como ESF. **No hace falta** que marques la casilla de gestionar facturas de terceros.
4. Crea una nueva plataforma de facturación y sube la **clave pública** del certificado que utilizarás para comunicarte con los Web Services. Puede ser la clave pública del certificado que hemos estado usando hasta ahora.

## Cliente de FACeB2B
El uso de FACeB2B desde Facturae-PHP es extremadamente sencillo:

```php
use josemmo\Facturae\Face\Faceb2bClient;

$face = new Faceb2bClient("clave_publica.pem", "clave_privada.pem", "passphrase");
```

Al igual que al firmar una factura electrónica, puedes usar un solo archivo `.pfx` en vez de un par `.pem`:
```php
$face = new FaceClient("certificado.pfx", null, "passphrase");
```

Por defecto, `Faceb2bClient` se comunica con el entorno de producción de FACeB2B. Para usar el entorno de pruebas (*staging*) puedes utilizar el siguiente método:
```php
$face->setProduction(false);
```

Todas las llamadas a FACeB2B desde `Faceb2bClient` devuelven un objeto `SimpleXMLElement`. Consulta el [manual de PHP](http://php.net/manual/simplexml.examples-basic.php) para más información.
