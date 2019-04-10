<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Face\FaceClient;
use josemmo\Facturae\Face\Faceb2bClient;

final class WebservicesTest extends AbstractTest {

  /**
   * Test FACe
   */
  public function testFace() {
    $face = new FaceClient(__DIR__ . "/certs/facturae.pfx", null, "12345");
    $face->setProduction(false);
    $res = $face->getStatus();

    $success = isset($res->estados);
    $this->assertTrue($success);
  }


  /**
   * Test FACeB2B
   */
  public function testFaceb2b() {
    $face = new Faceb2bClient(__DIR__ . "/certs/facturae.pfx", null, "12345");
    $face->setProduction(false);
    $res = $face->getCodes();

    $success = isset($res->resultStatus);
    $this->assertTrue($success);
  }

}
