<?php
namespace josemmo\Facturae\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase {

  const OUTPUT_DIR = __DIR__ . "/output";
  const CERTS_DIR = __DIR__ . "/certs";
  const FACTURAE_CERT_PASS = "12345";
  const WEBSERVICES_CERT_PASS = "G5cp,fYC9gje";
  const NOTIFICATIONS_EMAIL = "josemmo@pm.me";

}
