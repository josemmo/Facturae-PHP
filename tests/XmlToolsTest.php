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

}
