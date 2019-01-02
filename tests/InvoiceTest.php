<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
use josemmo\Facturae\FacturaeCentre;
use PHPUnit\Framework\TestCase;

final class FacturaeTest extends TestCase {

  const FILE_PATH = __DIR__ . "/salida-*.xsig";
  const COOKIES_PATH = __DIR__ . "/cookies.txt";

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
      "merchantRegister" => "RG"
    ]));

    // Incluimos los datos del comprador
    $fac->setBuyer(new FacturaeParty([
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
      "sequenceNumber" => "1.0"
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

    // Para terminar, añadimos 3 bombillas LED con un coste de 6,50 € ...
    // ... pero con los impuestos NO INCLUÍDOS en el precio unitario
    $fac->addItem(new FacturaeItem([
      "name" => "Bombilla LED",
      "quantity" => 3,
      "unitPriceWithoutTax" => 6.5, // NOTA: no confundir con unitPrice
      "taxes" => array(Facturae::TAX_IVA => 21)
    ]));

    // Añadimos una declaración responsable
    $fac->addLegalLiteral("Este es un mensaje de prueba que se incluirá " .
      "dentro del campo LegalLiterals del XML de la factura");
    $fac->addLegalLiteral("Y este, \"otro\" con 'caracteres' a <escapar>");

    // Añadimos recargos y descuentos sobre el total de la factura
    $fac->addDiscount('A mitad de precio', 50);
    $fac->addCharge('Recargo del 50%', 50);

    // Establecemos un método de pago (por coverage, solo en algunos casos)
    if (!$isPfx) {
      $fac->setPaymentMethod(Facturae::PAYMENT_TRANSFER, "ES7620770024003102575766");
      $fac->setDueDate("2017-12-31");
    }

    // Ya solo queda firmar la factura ...
    if ($isPfx) {
      $fac->sign(__DIR__ . "/test.pfx", null, "12345");
    } else {
      $fac->sign(__DIR__ . "/public.pem", __DIR__ . "/private.pem", "12345");
    }
    $fac->setTimestampServer("http://tss.accv.es:8318/tsa");

    // ... exportarlo a un archivo ...
    $isPfxStr = $isPfx ? "PKCS12" : "X509";
    $outputPath = str_replace("*", "$schemaVersion-$isPfxStr", self::FILE_PATH);
    $res = $fac->export($outputPath);

    // ... y validar la factura
    $this->validateInvoiceXML($outputPath);
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


  /**
   * Validate Invoice XML
   *
   * @param string $path Invoice path
   */
  private function validateInvoiceXML($path) {
    // Prepare file to upload
    if (function_exists('curl_file_create')) {
      $postFile = curl_file_create($path);
    } else {
      $postFile = "@" . realpath($path);
    }

    // Send upload request
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_URL => "http://plataforma.firma-e.com/VisualizadorFacturae/index2.jsp",
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => array(
        "referencia" => $postFile,
        "valContable" => "on",
        "valFirma" => "on",
        "aceptarCondiciones" => "on",
        "submit" => "Siguiente"
      ),
      CURLOPT_COOKIEJAR => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    if (strpos($res, "window.open('facturae.jsp'") === false) {
      $this->expectException(UnexpectedValueException::class);
    }

    // Fetch results
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_URL => "http://plataforma.firma-e.com/VisualizadorFacturae/facturae.jsp",
      CURLOPT_COOKIEFILE => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);

    // Validate results
    $this->assertNotEmpty($res);
    $this->assertContains('euro_ok.png', $res, 'Invalid XML Format');
    $this->assertContains('>Nivel de Firma Válido<', $res, 'Invalid Signature');
    $this->assertContains('>XAdES_T<', $res, 'Invalid Timestamp');
  }

}
