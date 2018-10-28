<?php
namespace josemmo\Facturae\Controller\Extensions;

use josemmo\Facturae\FacturaeCentre;

class Fb2bExtension extends Extension {

  private $publicSectorInfo = array();
  private $receiver = null;
  private $sellerCentres = array();
  private $buyerCentres = array();


  /**
   * Set public organism code
   * @param string $code Public organism code
   */
  public function setPublicOrganismCode($code) {
    $this->publicSectorInfo['code'] = $code;
  }


  /**
   * Set contract reference
   * @param string $contractReference Contract reference
   */
  public function setContractReference($contractReference) {
    $this->publicSectorInfo['contractReference'] = $contractReference;
  }


  /**
   * Set receiver administrative centre (buyer)
   * @param FacturaeCentre $receiver Invoice receiver
   */
  public function setReceiver($receiver) {
    $this->receiver = $receiver;
  }


  /**
   * Add centre
   * @param FacturaeCentre $centre  Administrative centre
   * @param boolean        $isBuyer Centre belongs to receiver's end (buyer)
   */
  public function addCentre($centre, $isBuyer=true) {
    if ($isBuyer) {
      $this->buyerCentres[] = $centre;
    } else {
      $this->sellerCentres[] = $centre;
    }
  }


  /**
   * Convert administrative centres array to XML string
   * @param  FacturaeCentre[] $centres Administrative centres
   * @return string                    XML response
   */
  private function centresToXml($centres) {
    if (count($centres) == 0) return "";
    $res = '<administrativeCentres>';
    foreach ($centres as $centre) {
      $res .= '<centre><code>' . $centre->code . '</code>';
      if (!empty($centre->name)) $res .= '<name>' . $centre->name . '</name>';
      $res .= '<role>' . $centre->role . '</role></centre>';
    }
    $res .= '</administrativeCentres>';
    return $res;
  }


  /**
   * Get additional data
   * @return string|null Additional XML data
   */
  public function __getAdditionalData() {
    if (empty($this->receiver)) return null;
    $res = '<fb2b:FaceB2BExtension xmlns:fb2b="http://www.facturae.es/Facturae/Extensions/FaceB2BExtensionv1_1">';

    // Add public sector information
    if (!empty($this->publicSectorInfo['code'])) {
      $res .= '<publicSectorInformation>';
      $res .= '<publicOrganismCode>' . $this->publicSectorInfo['code'] . '</publicOrganismCode>';
      if (!empty($this->publicSectorInfo['contractReference'])) {
        $res .= '<contractReference>' . $this->publicSectorInfo['contractReference'] . '</contractReference>';
      }
      $res .= '</publicSectorInformation>';
    }

    // Add buyers
    $res .= '<buyerCentres>';
    $res .= '<receiverAdministrativeCentre>';
    $res .= '<code>' . $this->receiver->code . '</code>';
    if (!empty($this->receiver->name)) {
      $res .= '<name>' . $this->receiver->name . '</name>';
    }
    $res .= '</receiverAdministrativeCentre>';
    $res .= $this->centresToXml($this->buyerCentres);
    $res .= '</buyerCentres>';

    // Add sellers
    if (count($this->sellerCentres) > 0) {
      $res .= '<sellerCentres>';
      $res .= $this->centresToXml($this->sellerCentres);
      $res .= '</sellerCentres>';
    }

    $res .= '</fb2b:FaceB2BExtension>';
    return $res;
  }

}
