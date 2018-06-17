<?php
use josemmo\Facturae\Face\FaceClient;
use PHPUnit\Framework\TestCase;

final class WebservicesTest extends TestCase {

  /**
   * Test FACe
   */
  public function testFace() {
    $face = new FaceClient(__DIR__ . "/test.pfx", null, "12345");
    $face->setProduction(false);
    $res = $face->getStatus();

    $success = isset($res->estados);
    $this->assertTrue($success);
  }

}
