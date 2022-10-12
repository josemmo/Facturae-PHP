<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaePayment;
use josemmo\Facturae\FacturaeCentre;

final class InvoiceTest extends AbstractTest {

  const FILE_PATH = self::OUTPUT_DIR . "/salida-*.xsig";

  /**
   * Test Create Invoice
   *
   * @param string  $schemaVersion FacturaE Schema Version
   * @param boolean $isPfx         Whether to test with PFX signature or PEM files
   *
   * @dataProvider  invoicesProvider
   */
  public function testCreateInvoice($schemaVersion, $isPfx) {
    // Creamos la factura
    $fac = new Facturae($schemaVersion);
    if ($isPfx) {
      $fac->setPrecision(Facturae::PRECISION_INVOICE);
    }

    // Asignamos el número EMP2017120003 a la factura
    // Nótese que Facturae debe recibir el lote y el
    // número separados
    $fac->setNumber('EMP201712', '0003');

    // Asignamos el 01/12/2017 como fecha de la factura
    $fac->setIssueDate('2017-12-01');

    // Y un periodo de facturación del mes anterior
    $fac->setBillingPeriod("2017-11-01", "2017-11-30");

    // Incluimos los datos del vendedor
    $fac->setSeller(new FacturaeParty([
      "taxNumber" => "A00000000",
      "name"      => "Perico el de los Palotes S.A.",
      "address"   => "C/ Falsa, 123",
      "postCode"  => "23456",
      "town"      => "Madrid",
      "province"  => "Madrid",
      "book"      => "0",
      "sheet"     => "1",
      "merchantRegister" => "RG",
      "phone"       => "910112233",
      "fax"         => "910112234",
      "email"       => "noexiste@ejemplo.com",
      "cnoCnae"     => "04647",
      "ineTownCode" => "0796"
    ]));

    // Incluimos los datos del comprador
    $fac->setBuyer(new FacturaeParty([
      "taxNumber" => "P2813400E",
      "name"      => "Ayuntamiento de San Sebastián de los Reyes",
      "address"   => "Plaza de la Constitución, 1",
      "postCode"  => "28701",
      "town"      => "San Sebastián de los Reyes",
      "province"  => "Madrid",
      "website"   => "http://www.ssreyes.org/es/",
      "contactPeople" => "Persona de contacto",
      "centres"   => [
        new FacturaeCentre([
          "role"     => FacturaeCentre::ROLE_GESTOR,
          "code"     => "L01281343",
          "name"     => "Intervención Municipal",
          "address"  => "Calle Falsa, 123",
          "postCode" => "12345",
          "town"     => "Springfield",
          "province" => "Springfield",
          "firstSurname" => "Nombre del Responsable",
          "lastSurname"  => "Apellidos del Responsable",
          "description"  => "Esta es una descripción de prueba"
        ]),
        new FacturaeCentre([
          "role"     => FacturaeCentre::ROLE_TRAMITADOR,
          "code"     => "L01281343",
          "name"     => "Intervención Municipal",
          "address"  => "Calle Falsa, 123" // No debería usarse esta calle, pues
                                           // faltan datos de la dirección
        ]),
        new FacturaeCentre([
          "role"     => FacturaeCentre::ROLE_CONTABLE,
          "code"     => "L01281343",
          "name"     => "Intervención Municipal"
        ])
      ]
    ]));

    // Añadimos los productos a incluir en la factura
    // En este caso, probaremos con tres lámpara por
    // precio unitario de 20,14€, IVA al 21% YA INCLUÍDO
    $fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

    // Y ahora, una línea con IVA al 0%
    $fac->addItem("Algo exento de IVA", 100, 1, Facturae::TAX_IVA, 0);

    // Vamos a añadir un producto utilizando la API avanzada
    // que tenga IVA al 10%, IRPF al 15%, descuento del 10% y recargo del 5%
    $fac->addItem(new FacturaeItem([
      "name" => "Una línea con varios impuestos",
      "articleCode" => 4012,
      "description" => "Esta línea es solo para probar Facturae-PHP",
      "quantity" => 1, // Esto es opcional, es el valor por defecto si se omite
      "unitPrice" => 43.64,
      "discounts" => array(
        ["reason"=>"Descuento del 10%", "rate"=>10],
        ["reason"=>"5€ de descuento", "amount"=>5]
      ),
      "charges" => array(
        ["reason"=>"Recargo del 5% bruto", "rate"=>5, "hasTaxes"=>false]
      ),
      "taxes" => array(
        Facturae::TAX_IVA  => 10,
        Facturae::TAX_IRPF => 15
      ),
      "issuerContractReference" => "A9938281",
      "issuerContractDate" => "2010-03-10",
      "issuerTransactionReference" => "A9938282",
      "issuerTransactionDate" => "2010-03-10",
      "receiverContractReference" => "BBBH-38271",
      "receiverContractDate" => "2010-03-10",
      "receiverTransactionReference" => "BBBH-38272",
      "receiverTransactionDate" => "2010-03-10",
      "fileReference" => "000298172",
      "fileDate" => "2010-03-10",
      "sequenceNumber" => "1.0",
      "periodStart" => "2022-01-01",
      "periodEnd" => "2022-01-31"
    ]));

    // Por defecto, Facturae-PHP asume que el IRPF es un impuesto retenido y el
    // IVA un impuesto repercutido. Para rizar un poco el rizo vamos a añadir
    // una línea con IVA (repercutido) al 21% y también con impuestos especiales
    // retenidos al 4%:
    $fac->addItem(new FacturaeItem([
      "name" => "Llevo impuestos retenidos",
      "fileReference" => "AH6227001",
      "quantity" => 1,
      "unitPrice" => 10,
      "taxes" => array(
        Facturae::TAX_IVA => 21,
        Facturae::TAX_IE  => ["rate"=>4, "isWithheld"=>true]
      )
    ]));

    // Un producto con IVA con recargo de equivalencia e IRPF
    $fac->addItem(new FacturaeItem([
      "name" => "Llevo IVA con recargo de equivalencia",
      "quantity" => 1,
      "unitPrice" => 10,
      "taxes" => [
        Facturae::TAX_IVA  => ["rate"=>21, "surcharge"=>5.2],
        Facturae::TAX_IRPF => 19
      ]
    ]));

    // Añadimos 3 bombillas LED con un coste de 6,50 € ...
    // ... pero con los impuestos NO INCLUÍDOS en el precio unitario
    $fac->addItem(new FacturaeItem([
      "name" => "Bombilla LED",
      "quantity" => 3,
      "unitPriceWithoutTax" => 6.5, // NOTA: no confundir con unitPrice
      "taxes" => array(Facturae::TAX_IVA => 21)
    ]));

    // Añadimos varias líneas para jugar con la precisión
    $fac->addItem("Para precisión #1", 37.76, 1, Facturae::TAX_IVA, 21);
    $fac->addItem("Para precisión #2", 21.67, 1, Facturae::TAX_IVA, 21);

    // Añadimos una declaración responsable
    $fac->addLegalLiteral("Este es un mensaje de prueba que se incluirá " .
      "dentro del campo LegalLiterals del XML de la factura");
    $fac->addLegalLiteral("Y este, \"otro\" con 'caracteres' a <escapar>");

    // Añadimos recargos y descuentos sobre el total de la factura
    $fac->addDiscount('A mitad de precio', 50);
    $fac->addCharge('Recargo del 50%', 50);

    // Establecemos un un cesionario (solo en algunos casos)
    if ($isPfx) {
      $fac->setAssignee(new FacturaeParty([
        "taxNumber" => "B00000000",
        "name"      => "Cesionario S.L.",
        "address"   => "C/ Falsa, 321",
        "postCode"  => "02001",
        "town"      => "Albacete",
        "province"  => "Albacete",
        "phone"     => "967000000",
        "fax"       => "967000001",
        "email"     => "cesionario@ejemplo.com"
      ]));
      $fac->setAssignmentClauses('Cláusula de cesión');
    }

    // Establecemos el/los método(s) de pago
    if ($isPfx) {
      $fac->addPayment(new FacturaePayment([
        "method"  => FacturaePayment::TYPE_CASH,
        "amount"  => 100
      ]));
      $fac->addPayment(new FacturaePayment([
        "method"  => FacturaePayment::TYPE_TRANSFER,
        "amount"  => 199.90002,
        "dueDate" => "2017-12-31",
        "iban"    => "ES7620770024003102575766",
        "bic"     => "CAHMESMM"
      ]));
    } else {
      $fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766", "CAHMESMM");
      $fac->setDueDate("2017-12-31");
    }

    // Añadimos datos adicionales
    $fac->setRelatedInvoice('AAA-01273S');
    $fac->setAdditionalInformation('Esta factura es una prueba generada por ' . Facturae::USER_AGENT);

    // Adjuntamos un documento
    $attachment = new FacturaeFile();
    $attachment->loadData('<?xml version="1.0" encoding="UTF-8"?><test><hola>mundo</hola></test>', 'adjunto.xml');
    $fac->addAttachment($attachment, 'Un documento XML muy pequeño');

    // Ya solo queda firmar la factura ...
    if ($isPfx) {
      $fac->sign(self::CERTS_DIR . "/facturae.p12", null, self::FACTURAE_CERT_PASS);
      $fac->setTimestampServer("http://tss.accv.es:8318/tsa");
    } else {
      $fac->sign(self::CERTS_DIR . "/facturae-public.pem",
                 self::CERTS_DIR . "/facturae-private.pem", self::FACTURAE_CERT_PASS);
    }

    // ... exportarlo a un archivo ...
    $isPfxStr = $isPfx ? "PKCS12" : "X509";
    $outputPath = str_replace("*", "$schemaVersion-$isPfxStr", self::FILE_PATH);
    $fac->export($outputPath);

    // ... y validar la factura
    $this->validateInvoiceXML($outputPath, true);
  }


  /**
   * Invoices provider
   */
  public function invoicesProvider() {
    return [
      "v3.2 (X.509)"     => [Facturae::SCHEMA_3_2,   false],
      "v3.2.1 (X.509)"   => [Facturae::SCHEMA_3_2_1, false],
      "v3.2.1 (PKCS#12)" => [Facturae::SCHEMA_3_2_1, true],
      "v3.2.2 (PKCS#12)" => [Facturae::SCHEMA_3_2_2, true]
    ];
  }

}
