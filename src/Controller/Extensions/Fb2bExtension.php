<?php
namespace josemmo\Facturae\Controller\Extensions;

class Fb2bExtension extends Extension {

  private $receiver = null;
  private $centres = array();


  /**
   * Set receiver
   * @param FacturaeCentre $receiver Invoice receiver
   */
  public function setReceiver($receiver) {
    $this->receiver = $receiver;
  }


  /**
   * Add centre
   * @param FacturaeCentre $receiver Administrative centre
   */
  public function addCentre($centre) {
    $this->centres[] = $centre;
  }


  /**
   * Get additional data
   * @return string|null Additional XML data
   */
  public function __getAdditionalData() {
    if (empty($this->receiver)) return null;
    $res = '<fb2b:FaceB2BExtension xmlns:fb2b="http://www.facturae.es/Facturae/Extensions/FB2B">';
    $res .= '<mainSubcontractorCentres>';

    // Add receiver
    $res .= '<receiverAdministrativeCentre>';
    $res .= '<code>' . $this->receiver->code . '</code>';
    if (!empty($this->receiver->name)) {
      $res .= '<name>' . $this->receiver->name . '</name>';
    }
    $res .= '</receiverAdministrativeCentre>';

    // Add administrative centres
    if (count($this->centres) > 0) {
      $res .= '<administrativeCentres>';
      foreach ($this->centres as $centre) {
        $res .= '<centre><code>' . $centre->code . '</code>';
        if (!empty($centre->name)) $res .= '<name>' . $centre->name . '</name>';
        $res .= '<role>' . $centre->role . '</role></centre>';
      }
      $res .= '</administrativeCentres>';
    }

    $res .= '</mainSubcontractorCentres>';
    $res .= '</fb2b:FaceB2BExtension>';
    return $res;
  }

}
