<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Facturae;

final class PerformanceTest extends AbstractTest {

  const ROUNDS = 100;
  const PRECISION = 10;

  /**
   * Test performance
   */
  public function testPerformance() {
    $start = microtime(true);

    for ($i=0; $i<self::ROUNDS; $i++) {
      $fac = $this->getBaseInvoice();
      $fac->addItem("Producto #$i", 20.14, 3, Facturae::TAX_IVA, 21);
      $fac->sign(self::CERTS_DIR . "/facturae.p12", null, self::FACTURAE_CERT_PASS);
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
