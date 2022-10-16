---
title: Firma avanzada
parent: Firma electrónica
nav_order: 2
permalink: /firma-electronica/firma-avanzada.html
---

# Firma avanzada
La librería permite firmar documentos XML de FacturaE que hayan sido generados con otros programas a través de la clase `FacturaeSigner`. Esta misma clase es usada a nivel interno para firmar las facturas creadas con Facturae-PHP.

```php
use josemmo\Facturae\Common\FacturaeSigner;
use RuntimeException;

// Creación y configuración de la instancia
$signer = new FacturaeSigner();
$signer->setSigningKey("certificado.pfx", null, "passphrase");
$signer->setTimestampServer("https://www.safestamper.com/tsa", "usuario", "contraseña");

// Firma electrónica
$xml = file_get_contents(__DIR__ . "/factura.xml");
try {
  $signedXml = $signer->sign($xml);
} catch (RuntimeException $e) {
  // Fallo al firmar
}

// Sellado de tiempo
try {
  $timestampedXml = $signer->timestamp($signedXml);
} catch (RuntimeException $e) {
  // Fallo al añadir sello de tiempo
}
file_put_contents(__DIR__ . "/factura.xsig", $timestampedXml);
```

`FacturaeSigner` es capaz de firmar cualquier documento XML válido que cumpla con la especificación de FacturaE, siempre y cuando:

- El elemento raíz del documento sea `<fe:Facturae />`
- El namespace de FacturaE sea `xmlns:fe`
- El namespace de XMLDSig no aparezca (recomendable) o sea `xmlns:ds`
- El namespace de XAdES no aparezca (recomendable) o sea `xmlns:xades`

La inmensa mayoría de programas que generan documentos de FacturaE cumplen con estos requisitos.

---

## Fecha de la firma
Por defecto, al firmar una factura se utilizan la fecha y hora actuales como sello de tiempo. Si se quiere indicar otro valor, se debe utilizar el siguiente método:
```php
$signer->setSigningTime("2017-01-01T12:34:56+02:00");
```

> #### NOTA
> Cambiar manualmente la fecha de la firma puede entrar en conflicto con el sellado de tiempo.

---

## Identificadores de elementos XML
Al firmar un documento XML, durante la firma se añaden una serie de identificadores a determinados nodos en forma de atributos (por ejemplo, `<xades:SignedProperties Id="Signature1234-SignedProperties9876" />`).
Estos identificadores son necesarios para validar la firma e integridad del documento.

Por defecto, sus valores se generan de forma aleatoria en el momento de **instanciación** de la clase `FacturaeSigner`, por que lo que si se firman varias facturas con la misma instancia sus valores no cambian.
Se recomienda llamar al método `$signer->regenerateIds()` si se firman varios documentos:

```php
$firstXml = file_get_contents(__DIR__ . "/factura_1.xml");
$firstSignedXml = $signer->sign($firstXml);

$signer->regenerateIds();

$secondXml = file_get_contents(__DIR__ . "/factura_2.xml");
$secondSignedXml = $signer->sign($secondXml);
```

También es posible establecer valores deterministas a todos los IDs:

```php
$signer->signatureId = "My-Custom-SignatureId";
$signer->certificateId = "My-Custom-CertificateId";
$signedXml = $signer->sign($xml);
```
