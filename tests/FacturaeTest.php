<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;
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

    // Incluimos los datos del vendedor
    $fac->setSeller(new FacturaeParty([
      "taxNumber" => "A00000000",
      "name"      => "Perico el de los Palotes S.A.",
      "address"   => "C/ Falsa, 123",
      "postCode"  => "23456",
      "town"      => "Madrid",
      "province"  => "Madrid",
      "book"      => "0",
      "merchantRegister" => "RG",
      "sheet"     => "1"
    ]));

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
      "postCode"      => "65432",
      "town"          => "Madrid",
      "province"      => "Madrid"
    ]));

    // Añadimos los productos a incluir en la factura
    // En este caso, probaremos con tres lámpara por
    // precio unitario de 20,14€, IVA al 21% YA INCLUÍDO
    $fac->addItem("Lámpara de pie", 20.14, 3, Facturae::TAX_IVA, 21);

    // Y ahora, una línea con IVA al 0%
    $fac->addItem("Algo exento de IVA", 100, 1, Facturae::TAX_IVA, 0);

    // Vamos a añadir un producto utilizando la API avanzada
    // que tenga IVA al 10% e IRPF al 15%
    $fac->addItem(new FacturaeItem([
      "name" => "Una línea con varios impuestos",
      "description" => "Esta línea es solo para probar Facturae-PHP",
      "quantity" => 1, // Esto es opcional, es el valor por defecto si se omite
      "unitPrice" => 43.64,
      "taxes" => array(
        Facturae::TAX_IVA  => 10,
        Facturae::TAX_IRPF => 15
      )
    ]));

    // Por defecto, Facturae-PHP asume que el IRPF es un impuesto retenido y el
    // IVA un impuesto repercutido. Para rizar un poco el rizo vamos a añadir
    // una línea con IVA (repercutido) al 21% y también con impuestos especiales
    // retenidos al 4%:
    $fac->addItem(new FacturaeItem([
      "name" => "Llevo impuestos retenidos",
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
    $fac->addLegalLiteral("Y este, otro (se pueden añadir varios)");

    // Ya solo queda firmar la factura ...
    if ($isPfx) {
      $fac->sign(__DIR__ . "/test.pfx", NULL, "12345");
    } else {
      $fac->sign(__DIR__ . "/public.pem", __DIR__ . "/private.pem", "12345");
    }

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
    // TODO: uncomment last two tests
    // Not ready for production as almost no provider supports v3.2.2,
    // not even the Spanish Goverment itself. Maybe in 2018?
    return [
      "v3.2.1 (X.509)"   => [Facturae::SCHEMA_3_2_1, false],
      "v3.2.1 (PKCS#12)" => [Facturae::SCHEMA_3_2_1, true],
      //"v3.2.2 (X.509)"   => [Facturae::SCHEMA_3_2_2, false],
      //"v3.2.2 (PKCS#12)" => [Facturae::SCHEMA_3_2_2, true]
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
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_URL => "https://viewer.facturadirecta.com/dp/viewer/upload.void",
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => array("xmlFile" => $postFile),
      CURLOPT_COOKIEJAR => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    if (strpos($res, "<h1>Ok</h1>") === false) {
      $this->expectException(UnexpectedValueException::class);
    }

    // Fetch results
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_URL => "https://viewer.facturadirecta.com/dp/viewer/viewer.void",
      CURLOPT_POST => 1,
      CURLOPT_COOKIEFILE => self::COOKIES_PATH
    ));
    $res = curl_exec($ch);
    curl_close($ch);

    // Parse results
    $res = explode('<json><![CDATA[', $res);
    $res = explode(']]></json>', $res[1]);
    $res = json_decode(trim($res[0]));
    $this->assertEquals($res->result, true);
  }

}
