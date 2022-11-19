---
title: Firma electrónica
nav_order: 5
has_children: true
permalink: /firma-electronica/
---

# Firma electrónica
Aunque es posible exportar las facturas sin firmarlas, es un paso obligatorio para prácticamente cualquier trámite relacionado con la Administración Pública.
Para firmar facturas se necesita un certificado electrónico (generalmente expedido por la FNMT) del que extraer su clave pública y su clave privada.

## Firmado con clave pública y privada X.509
Si se tiene la clave pública (un certificado) y la clave privada en archivos independientes, se debe utilizar este método con los siguientes argumentos:
```php
$fac->sign("clave_publica.pem", "clave_privada.pem", "passphrase");
```

También se pueden pasar como parámetros los bytes de ambos ficheros en vez de sus rutas, o instancias de `OpenSSLCertificate` y `OpenSSLAsymmetricKey`, respectivamente:
```php
$publicKey = openssl_x509_read("clave_publica.pem");
$encryptedPrivateKey = file_get_contents("clave_privada.pem");
$fac->sign($publicKey, $encryptedPrivateKey, "passphrase");
```

> #### NOTA
> Los siguientes comandos permiten extraer el certificado (clave pública) y la clave privada de un archivo PFX:
>
> ```
> openssl pkcs12 -in certificado_de_entrada.pfx -clcerts -nokeys -out clave_publica.pem
> openssl pkcs12 -in certificado_de_entrada.pfx -nocerts -out clave_privada.pem
> ```

---

## Firmado con PKCS#12
Desde la versión 1.0.5 de Facturae-PHP ya es posible cargar un banco de certificados desde un archivo `.pfx` o `.p12` sin necesidad de convertirlo previamente a X.509:
```php
$fac->sign("certificado.pfx", null, "passphrase");
```

También se pueden pasar como parámetro los bytes del banco PKCS#12:
```php
$encryptedStore = file_get_contents("certificado.pfx");
$fac->sign($encryptedStore, null, "passphrase");
```

> #### NOTA
> Al utilizar un banco PKCS#12, Facturae-PHP incluirá la cadena completa de certificados en la factura al firmarla.
>
> Aunque en la mayoría de los casos esto no supone ninguna diferencia con respecto a firmar desde ficheros PEM, el validador del Gobierno de España **presenta problemas para verificar firmas de certificados expedidos recientemente por la FNMT**.
> Dicho problema se soluciona cuando se incluyen los certificados raíz e intermedios de la Entidad de Certificación, por lo que es recomendable usar este método de firma con Facturae-PHP.

> #### NOTA
> A partir de OpenSSL v3.0.0, algunos algoritmos de digest como RC4 fueron [marcados como obsoletos](https://www.openssl.org/docs/man3.0/man7/migration_guide.html#Deprecated-low-level-encryption-functions).
> Esto puede suponer un problema para bancos de certificados exportados desde el Gestor de Certificados de Windows.
> Se recomienda validar estos ficheros antes de usarlos en la librería:
>
> ```
> openssl pkcs12 -in certificado.pfx -info -nokeys -nocerts
> ```

---

## Fecha de la firma
Por defecto, al firmar una factura se utilizan la fecha y hora actuales como sello de tiempo. Si se quiere indicar otro valor, se debe utilizar el siguiente método:
```php
$fac->setSigningTime("2017-01-01T12:34:56+02:00");
```

> #### NOTA
> Cambiar manualmente la fecha de la firma puede entrar en conflicto con el sellado de tiempo.
