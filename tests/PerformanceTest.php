<?php
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeParty;
use PHPUnit\Framework\TestCase;

final class PerformanceTest extends TestCase {

  const ROUNDS = 100;
  const PRECISION = 10;

  /**
   * Test performance
   */
  public function testPerformance() {
    $start = microtime(true);

    for ($i=0; $i<self::ROUNDS; $i++) {
      $fac = new Facturae();
      $fac->setNumber('FAC201804', '123');
      $fac->setIssueDate('2018-04-01');
      $fac->setSeller(new FacturaeParty([
        "taxNumber" => "A00000000",
        "name"      => "Perico de los Palotes S.A.",
        "address"   => "C/ Falsa, 123",
        "postCode"  => "12345",
        "town"      => "Madrid",
        "province"  => "Madrid"
      ]));
      $fac->setBuyer(new FacturaeParty([
        "isLegalEntity" => false,
        "taxNumber"     => "00000000A",
        "name"          => "Antonio",
        "firstSurname"  => "García",
        "lastSurname"   => "Pérez",
        "address"       => "Avda. Mayor, 7",
        "postCode"      => "54321",
        "town"          => "Madrid",
        "province"      => "Madrid"
      ]));
      $fac->addItem("Producto #$i", 20.14, 3, Facturae::TAX_IVA, 21);
      $fac->sign(__DIR__ . "/test.pfx", null, "12345");
      $fac->export();
    }

    $end = microtime(true);
    $diff = round($end - $start, self::PRECISION);
    $avg = round($diff / self::ROUNDS, self::PRECISION);

    echo "\n┌─────────────────────────────────────┐";
    echo "\n│      PERFORMANCE TEST RESULTS       │";
    echo "\n│                                     │";
    echo "\n│ Number of tests  : " . str_pad(self::ROUNDS, 16) . " │";
    echo "\n│ Total time       : " . str_pad($diff . " µs", 16) . "  │";
    echo "\n│ Avg. time x test : " . str_pad($avg . " µs", 16) . "  │";
    echo "\n└─────────────────────────────────────┘";
    $this->assertTrue($avg < 5);
  }

}
