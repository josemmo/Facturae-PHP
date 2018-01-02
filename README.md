# Facturae-PHP [![Build Status](https://travis-ci.org/josemmo/Facturae-PHP.svg?branch=master)](https://travis-ci.org/josemmo/Facturae-PHP)

## Qué es
Facturae-PHP es una clase escrita puramente en PHP que permite generar facturas siguiendo el formato estructurado de factura electrónica [Facturae](http://www.facturae.gob.es/) e incluso firmarlas con firma electrónica XAdES sin necesidad de ninguna librería o clase adicional.

### Requisitos
 - PHP 5.6 o superior
 - OpenSSL (solo para firmar facturas)

### Características
- [x] Generación de facturas 100% conformes con la [Ley 25/2013 del 27 de diciembre](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2013-13722) listas para enviar a FACe
- [x] Exportación según las versiones [3.2, 3.2.1 y 3.2.2](http://www.facturae.gob.es/formato/Paginas/version-3-2.aspx) de Facturae
- [x] Firmado de acuerdo a la [política de firma de Facturae 3.1](http://www.facturae.gob.es/formato/Paginas/politicas-firma-electronica.aspx) basada en XAdES

### Funciones previstas
- [ ] Firma con sellado de tiempo (TSA)
- [ ] Envío de facturas a FACe directamente desde la clase

---------------------------------------------------------

## Uso del paquete
Facturae-PHP pretende ser una clase extremadamente rápida y sencilla de usar. A continuación se incluyen varios ejemplos sobre su utilización.
Para más información sobre todos los métodos de Facturae-PHP, la clase se encuentra comentada según bloques de código de [phpDocumentor](https://www.phpdoc.org/).

### Instalación

```
composer require josemmo/facturae-php
```

### Ejemplo básico usando Composer
```php
// Sistema de carga automática de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Importamos las clases usadas
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaeCentre; // Esta es opcional
use josemmo\Facturae\FacturaeItem;   // Este es opcional

// Creamos la factura
$fac = new Facturae();

// Asignamos el número EMP2017120003 a la factura
// Nótese que Facturae debe recibir el lote y el
// número separados
$fac->setNumber('EMP201712', '0003');

// Asignamos el 01/12/2017 como fecha de la factura
$fac->setIssueNumber('2017-12-01');

// Incluimos los datos del vendedor
$fac->setSeller(new FacturaeParty([
    "taxNumber" => "A00000000",
    "name"      => "Perico el de los Palotes S.A.",
    "address"   => "C/ Falsa, 123",
    "postCode"  => "123456",
    "town"      => "Madrid",
    "province"  => "Madrid"
]);

// Incluimos los datos del comprador
// Con finos demostrativos el comprador será
// una persona física en vez de una empresa
$fac->setBuyer(new FacturaeParty([
    "isLegalEntity" => false,       // Importante!
    "taxNumber"     => "00000000A",
    "name"          => "Antonio",
    "firstSurname"  => "García",
    "lastSurname"   => "Pérez",
    "address"       => "Avda. Mayor, 7",
    "postCode"      => "654321",
    "town"          => "Madrid",
    "province"      => "Madrid"
]);

// Añadimos los productos a incluir en la factura
// En este caso, probaremos con tres lámpara por
// precio unitario de 20,14€ con 21% de IVA ya incluído
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

// Ya solo queda firmar la factura ...
$fac->sign(
    "ruta/hacia/clave_publica.pem",
    "ruta/hacia/clave_privada.pem",
    "passphrase"
);

// ... y exportarlo a un archivo
$fac->export("ruta/de/salida.xsig");
```

> ##### NOTA
> En caso de no utilizar Composer, se deberá sustituir en el ejemplo anterior la línea de código:
> ```php
> require_once __DIR__ . '/../vendor/autoload.php';
> ```
> Por las siguientes:
> ```php
> require_once "../src/Facturae.php";
> require_once "../src/FacturaeParty.php";
> require_once "../src/FacturaeCentre.php";
> require_once "../src/FacturaeItem.php";
> ```

### Versión de Facturae
Por defecto el paquete creará la factura siguiendo el formato Facturae 3.2.1 al ser actualmente el más extendido. Si se quisiera utilizar otra versión se deberá indicar al instanciar el objeto de la factura:
```php
$fac = new Facturae(Facturae::SCHEMA_3_2_2);
```

### Compradores y vendedores
Los compradores y vendedores son representados en Facturae-PHP con la clase `FacturaeParty` y pueden contener los siguientes atributos:
```php
$empresa = new FacturaeParty([
    "isLegalEntity" => true, // Se asume true si se omite
    "taxNumber"     => "A00000000",
    "name"          => "Perico el de los Palotes S.A.",
    "address"       => "C/ Falsa, 123",
    "postCode"      => "123456",
    "town"          => "Madrid",
    "province"      => "Madrid",
    "countryCode"   => "ESP",  // Se asume España si se omite
    "book"             => "0",  // Libro
    "merchantRegister" => "RG", // Registro Mercantil
    "sheet"            => "1",  // Hoja
    "folio"            => "2",  // Folio
    "section"          => "3",  // Sección
    "volume"           => "4",  // Tomo
    "email"   => "contacto@perico.com"
    "phone"   => "910555444",
    "fax"     => "910555443"
    "website" => "http://www.perico.com/",
    "contactPeople" => "Perico",
    "cnoCnae" => "4791", // Clasif. Nacional de Act. Económicas
    "ineTownCode" => "280796" // Cód. de municipio del INE
]);

$personaFisica = new FacturaeParty([
    "isLegalEntity" => false,
    "taxNumber"     => "00000000A",
    "name"          => "Antonio",
    "firstSurname"  => "García",
    "lastSurname"   => "Pérez",
    "address"       => "Avda. Mayor, 7",
    "postCode"      => "654321",
    "town"          => "Madrid",
    "province"      => "Madrid",
    "countryCode"   => "ESP",  // Se asume España si se omite
    "email"   => "antonio@email.com"
    "phone"   => "910777888",
    "fax"     => "910777888"
    "website" => "http://www.antoniogarcia.es/",
    "contactPeople" => "Antonio García",
    "cnoCnae" => "4791", // Clasif. Nacional de Act. Económicas
    "ineTownCode" => "280796" // Cód. de municipio del INE
]);
```

### Centros administrativos
Para poder emitir facturas a organismos públicos a través de FACe es necesario indicar los centros administrativos relacionados con la administración a facturar.
Estos datos pueden obtenerse a través del [directorio de organismos de FACe](https://face.gob.es/es/directorio/administraciones/) y deben ser indicados a Facturae-PHP de la siguiente forma:

```php
// Tomamos como ejemplo el Ayuntamiento de San Sebastián de los Reyes
$ayto = new FacturaeParty([
    "taxNumber" => "P2813400E",
    "name"      => "Ayuntamiento de San Sebastián de los Reyes",
    "address"   => "Plaza de la Constitución, 1",
    "postCode"  => "28701",
    "town"      => "San Sebastián de los Reyes",
    "province"  => "Madrid",
    "centres"   => [
        new FacturaeCentre([
            "role"     => FacturaeCentre::ROLE_GESTOR,
            "code"     => "L01281343",
            "name"     => "Intervención Municipal",
            "address"  => "Plaza de la Constitución, 1",
            "postCode" => "28701",
            "town"     => "San Sebastián de los Reyes",
            "province" => "Madrid"
        ]),
        new FacturaeCentre([
            "role"     => FacturaeCentre::ROLE_TRAMITADOR,
            "code"     => "L01281343",
            "name"     => "Intervención Municipal",
            "address"  => "Plaza de la Constitución, 1",
            "postCode" => "28701",
            "town"     => "San Sebastián de los Reyes",
            "province" => "Madrid"
        ]),
        new FacturaeCentre([
            "role"     => FacturaeCentre::ROLE_CONTABLE,
            "code"     => "L01281343",
            "name"     => "Intervención Municipal",
            "address"  => "Plaza de la Constitución, 1",
            "postCode" => "28701",
            "town"     => "San Sebastián de los Reyes",
            "province" => "Madrid"
        ])
    ]
]);
```

### Uso avanzado de líneas de producto
Es posible añadir una descripción además del concepto al añadir un producto al pasar un `array` de dos elementos en el primer parámetro del método:

```php
// Solo concepto, sin descripción
$fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

// Con descripción incluída
$fac->addItem(["Lámpara de pie", "Lámpara de madera de nogal con base rectangular y luz halógena"], 20.14, 3, Facturae::TAX_IVA, 21);
```

También se puede añadir un elemento sin impuestos, aunque solo debe hacerse en aquellos casos en los que se tenga total certeza:

```php
$fac->addItem("No llevo impuestos", 100, 1);
```

La forma adecuada de añadir elementos a los que no se les aplica IVA es la siguiente:

```php
$fac->addItem("Llevo IVA al 0%", 100, 1, Facturae::TAX_IVA, 0);
```

Nótese que Facturae-PHP no limita este tipo de comportamientos, es responsabilidad del usuario crear la factura de acuerdo a la legislación aplicable.

### Uso aún más avanzado de líneas de producto
Supongamos que se quieren añadir varios impuestos a una misma línea de producto. En este caso se deberá hacer uso de la API avanzada de productos de Facturae-PHP a través de la clase `FacturaeItem`:

```php
// Vamos a añadir un producto utilizando la API avanzada
// que tenga IVA al 10% e IRPF al 15%
$fac->addItem(new FacturaeItem([
    "name" => "Una línea con varios impuestos",
    "description" => "Esta línea es solo para probar Facturae-PHP",
    "quantity" => 1, // Esto es opcional, es el valor por defecto si se omite
    "unitPrice" => 43.64,
    "taxes" => [
        Facturae::TAX_IVA  => 10,
        Facturae::TAX_IRPF => 15
    ]
]));
```

Esta API también permite indicar el importe unitario del producto sin incluir impuestos (base imponible), ya que por defecto Facturae-PHP asume lo contrario:

```php
// Vamos a añadir 3 bombillas LED con un coste de 6,50 € ...
// ... pero con los impuestos NO INCLUÍDOS en el precio unitario
$fac->addItem(new FacturaeItem([
    "name" => "Bombilla LED",
    "quantity" => 3,
    "unitPriceWithoutTax" => 6.5, // NOTA: no confundir con unitPrice
    "taxes" => [Facturae::TAX_IVA => 21]
]));
```

Como último apunte sobre la API avanzada de productos, por defecto Facturae-PHP interprenta al IRPF como un impuesto retenido (aquellos que se restan a la base imponible) y al resto de impuestos como repercutidos (se suman a la base imponible).

Si necesitas crear una factura "especial" es posible sobreescribir el comportamiento por defecto a través de la propiedad `isWithheld`:

```php
// Para rizar un poco el rizo vamos a añadir una línea con IVA (repercutido)
// al 21% y también con impuestos especiales retenidos al 4%
$fac->addItem(new FacturaeItem([
  "name" => "Llevo impuestos retenidos",
  "quantity" => 1,
  "unitPrice" => 10,
  "taxes" => array(
    Facturae::TAX_IVA => 21,
    Facturae::TAX_IE  => ["rate"=>4, "isWithheld"=>true]
  )
]));
```

### Forma de pago y vencimiento
Es posible indicar la forma de pago de una factura. Por ejemplo, en caso de pagarse al contado:

```php
$fac->setPaymentMethod(Facturae::PAYMENT_CASH);
```

En caso de transferencia también debe indicarse la cuenta bancaria destinataria:

```php
$fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766");
```

Para establecer la fecha de vencimiento del pago:

```php
$fac->setDueDate("2017-12-31");
```

Por defecto, si se establece una forma de pago y no se indica la fecha de vencimiento se interpreta la fecha de la factura como tal.

### Firmado de facturas
Aunque es posible exportar las facturas sin firmarlas, es un paso obligatorio para prácticamente cualquier trámite relacionado con la Administración Pública.
Para firmar facturas se necesita un certificado electrónico (generalmente expedido por la FNMT) del que extraer su clave pública y su clave privada.

Por defecto, al firmar una factura se utilizan la fecha y hora actuales como sello de tiempo. Si se quiere indicar otro valor, se debe utilizar el siguiente método:

```php
$fac->setSignTime("2017-01-01T12:34:56+02:00");
```

Llegados a este punto hay dos formas de facilitar estos datos a Facturae-PHP:

#### Firmado con clave pública y privada X.509
Si se tienen las clave pública y privada en archivos independientes se debe utilizar este método con los siguientes argumentos:

```php
$fac->sign("clave_publica.pem", "clave_privada.pem", "passphrase");
```

> Los siguientes comandos permiten extraer el certificado (clave pública) y la clave privada de un archivo PFX:
>
> ```
> openssl pkcs12 -in certificado_de_entrada.pfx -clcerts -nokeys -out clave_publica.pem
> openssl pkcs12 -in certificado_de_entrada.pfx -nocerts -out clave_privada.pem
> ```

#### Firmado con PKCS#12
Desde la versión 1.0.5 de Facturae-PHP ya es posible cargar un banco de certificados desde un archivo `.pfx` o `.p12` sin necesidad de convertirlo previamente a X.509:

```php
$fac->sign("certificado.pfx", NULL, "passphrase");
```

### Otros métodos

#### Periodo de facturación

Es posible establecer el periodo de facturación con el siguiente método:

```php
$fac->setBillingPeriod("2017-11-01", "2017-11-30");
```

#### Textos literales

Se pueden incluir textos literales (generalmente, declaraciones responsables del proveedor) en el XML de la factura:

```php
$fac->addLegalLiteral("Este es un mensaje de prueba");
$fac->addLegalLiteral("Y este, otro más");
```

> ##### NOTA
> El uso de `LegalLiterals` es obligatorio en determinadas facturas. Consulta la legislación vigente para más información.

#### Totales de la factura

También es posible obtener un `array` con los totales de la factura:

```php
$totales = $fac->getTotals();
```

> ##### NOTA
> El método `getTotals` se ha dejado público al considerar que puede ser de gran utilidad para muchos programadores.
>
> Aún así, este método es utilizado por la clase principal para generar el documento XML de la factura y, por tanto, **su salida está sujeta a cambios frecuentes** para acomodar futuras funciones.
>
> En conclusión: si usas este método en tu proyecto lee los cambios de versión antes de actualizar.

---------------------------------------------------------

## Anexos

### Constantes

|Constante|Descripción|
|--------:|:----------|
|`Facturae::SCHEMA_3_2`|Formato de Facturae 3.2|
|`Facturae::SCHEMA_3_2_1`|Formato de Facturae 3.2.1|
|`Facturae::SCHEMA_3_2_2`|Formato de Facturae 3.2.2|
|`Facturae::SIGN_POLICY_3_1`|Formato de firma 3.1|
|||
|`Facturae::PAYMENT_CASH`|Pago al contado|
|`Facturae::PAYMENT_TRANSFER`|Pago por transferencia|
|||
|`Facturae::TAX_IVA`|Impuesto sobre el valor añadido|
|`Facturae::TAX_IPSI`|Impuesto sobre la producción, los servicios y la importación|
|`Facturae::TAX_IGIC`|Impuesto general indirecto de Canarias|
|`Facturae::TAX_IRPF`|Impuesto sobre la Renta de las personas físicas|
|`Facturae::TAX_OTHER`|Otro|
|`Facturae::TAX_ITPAJD`|Impuesto sobre transmisiones patrimoniales y actos jurídicos documentados|
|`Facturae::TAX_IE`|Impuestos especiales|
|`Facturae::TAX_RA`|Renta aduanas|
|`Facturae::TAX_IGTECM`|Impuesto general sobre el tráfico de empresas que se aplica en Ceuta y Melilla|
|`Facturae::TAX_IECDPCAC`|Impuesto especial sobre los combustibles derivados del petróleo en la Comunidad Autonoma Canaria|
|`Facturae::TAX_IIIMAB`|Impuesto sobre las instalaciones que inciden sobre el medio ambiente en la Baleares|
|`Facturae::TAX_ICIO`|Impuesto sobre las construcciones, instalaciones y obras|
|`Facturae::TAX_IMVDN`|Impuesto municipal sobre las viviendas desocupadas en Navarra|
|`Facturae::TAX_IMSN`|Impuesto municipal sobre solares en Navarra|
|`Facturae::TAX_IMGSN`|Impuesto municipal sobre gastos suntuarios en Navarra|
|`Facturae::TAX_IMPN`|Impuesto municipal sobre publicidad en Navarra|
|`Facturae::TAX_REIVA`|Régimen especial de IVA para agencias de viajes|
|`Facturae::TAX_REIGIC`|Régimen especial de IGIC: para agencias de viajes|
|`Facturae::TAX_REIPSI`|Régimen especial de IPSI para agencias de viajes|
|||
|`FacturaeCentre::ROLE_CONTABLE`<br>o `FacturaeCentre::ROLE_FISCAL`|Oficina contable|
|`FacturaeCentre::ROLE_GESTOR`<br>o `FacturaeCentre::ROLE_RECEPTOR`|Órgano gestor|
|`FacturaeCentre::ROLE_TRAMITADOR`<br>o `FacturaeCentre::ROLE_PAGADOR`|Unidad tramitadora|
|`FacturaeCentre::ROLE_PROPONENTE`|Órgano proponente|

### Herramientas de validación
Todas las facturas generadas y firmadas con Facturae-PHP son probadas con las siguientes herramientas online para garantizar el cumplimiento del estándar:

- https://viewer.facturadirecta.com/ (no soporta 3.2.2)
- http://plataforma.firma-e.com/VisualizadorFacturae/ (no soporta 3.2.2)
- http://sedeaplicaciones2.minetur.gob.es/FacturaE/index.jsp
- https://face.gob.es/es/facturas/validar-visualizar-facturas
