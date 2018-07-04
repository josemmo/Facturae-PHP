<?php
use josemmo\Facturae\Face\FaceClient;
use josemmo\Facturae\Face\Faceb2bClient;
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


  /**
   * Test FACeB2B
   */
  public function testFaceb2b() {
    $face = new Faceb2bClient(__DIR__ . "/test.pfx", null, "12345");
    $face->setProduction(false);
    $res = $face->getCodes();

    $success = isset($res->resultStatus);
    $this->assertTrue($success);
  }

}
