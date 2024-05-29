<?php
namespace josemmo\Facturae\Tests;

use josemmo\Facturae\Common\XmlTools;

final class XmlToolsTest extends AbstractTest {

  public function testCanGetNamespaces() {
    $xmlns = XmlTools::getNamespaces("<a:root xmlns:a=\"abc\"    xmlns:b='xyz' \r\nxmlns:c=\"o o o\"><a:root>");
    $this->assertEquals([
      'xmlns:a' => 'abc',
      'xmlns:b' => 'xyz',
      'xmlns:c' => 'o o o',
    ], $xmlns);

    $xmlns = XmlTools::getNamespaces('<a:root xmlns:a="abc" a:attr="hey" xmlns:b="xyz" attr="ho"><a:root>');
    $this->assertEquals([
      'xmlns:a' => 'abc',
      'xmlns:b' => 'xyz'
    ], $xmlns);
  }


  public function testCanInjectNamespaces() {
    $xml = XmlTools::injectNamespaces('<hello><a   /><b>Hey</b></hello>', ['xmlns:abc' => 'abc']);
    $this->assertEquals('<hello xmlns:abc="abc"><a   /><b>Hey</b></hello>', $xml);

    $xml = XmlTools::injectNamespaces("<a:root xmlns:a=\"abc\"    xmlns:b='xyz' \r\nxmlns:c=\"o o o\"><a:root>", [
      'test' => 'A test value',
      'xmlns:b' => 'XXXX',
      'xmlns:zzz' => 'Last namespace'
    ]);
    $this->assertEquals(
      '<a:root xmlns:a="abc" xmlns:b="XXXX" xmlns:c="o o o" xmlns:zzz="Last namespace" test="A test value"><a:root>',
      $xml
    );
  }


  public function testCanCanonicalizeXml() {
    $c14n = XmlTools::c14n('<elem><![CDATA[äëïöü]]></elem><elem><![CDATA[This is a <test>]]></elem>');
    $this->assertEquals('<elem>äëïöü</elem><elem>This is a &lt;test&gt;</elem>', $c14n);

    $c14n = XmlTools::c14n('<abc:hello><xyz:world /><earth /><everyone/></abc:hello>');
    $this->assertEquals('<abc:hello><xyz:world></xyz:world><earth></earth><everyone></everyone></abc:hello>', $c14n);
  }


  public function testCanGenerateDistinguishedNames() {
      $this->assertEquals(
        'CN=EIDAS CERTIFICADO PRUEBAS - 99999999R, SN=EIDAS CERTIFICADO, GN=PRUEBAS, OID.2.5.4.5=IDCES-99999999R, C=ES',
        XmlTools::getCertDistinguishedName([
          'C' => 'ES',
          'serialNumber' => 'IDCES-99999999R',
          'GN' => 'PRUEBAS',
          'SN' => 'EIDAS CERTIFICADO',
          'CN' => 'EIDAS CERTIFICADO PRUEBAS - 99999999R'
        ])
      );
      $this->assertEquals(
        'OID.2.5.4.97=VATFR-12345678901, CN=A Common Name, OU=Field, OU=Repeated, C=FR',
        XmlTools::getCertDistinguishedName([
          'C' => 'FR',
          'OU' => ['Repeated', 'Field'],
          'CN' => 'A Common Name',
          'ignoreMe' => 'This should not be here',
          'organizationIdentifier' => 'VATFR-12345678901',
        ])
      );
      $this->assertEquals(
        'OID.2.5.4.97=VATES-A11223344, CN=ACME ROOT, OU=ACME-CA, O=ACME Inc., L=Barcelona, C=ES',
        XmlTools::getCertDistinguishedName([
          'C' => 'ES',
          'L' => 'Barcelona',
          'O' => 'ACME Inc.',
          'OU' => 'ACME-CA',
          'CN' => 'ACME ROOT',
          'UNDEF' => 'VATES-A11223344'
        ])
      );
      $this->assertEquals(
        'OID.2.5.4.97=#0c0f56415445532d413030303030303030, CN=Common Name (UTF-8), OU=Unit, O=Organization, C=ES',
        XmlTools::getCertDistinguishedName([
          'C' => 'ES',
          'O' => 'Organization',
          'OU' => 'Unit',
          'CN' => 'Common Name (UTF-8)',
          'UNDEF' => '#0c0f56415445532d413030303030303030'
        ])
      );
      $this->assertEquals(
        'OID.2.5.4.97=#130f56415445532d413636373231343939, CN=Common Name (printable), OU=Unit, O=Organization, C=ES',
        XmlTools::getCertDistinguishedName([
          'C' => 'ES',
          'O' => 'Organization',
          'OU' => 'Unit',
          'CN' => 'Common Name (printable)',
          'UNDEF' => '#130f56415445532d413636373231343939'
        ])
      );
  }

}
