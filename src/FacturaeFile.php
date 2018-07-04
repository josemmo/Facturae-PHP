<?php
namespace josemmo\Facturae;

/**
 * Facturae File
 *
 * Represents a file that can be used as an attachment for an invoice or to
 * send information to a Web Service.
 */
class FacturaeFile {

  private $filename;
  private $data;
  private $mime;


  /**
   * Load data
   * @param string $data     File data
   * @param string $filename Filename
   */
  public function loadData($data, $filename) {
    $this->data = $data;
    $this->setFilename($filename);

    // Load MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $this->mime = finfo_buffer($finfo, $data);
    finfo_close($finfo);
  }


  /**
   * Load file
   * @param string      $path     Path to file
   * @param string|null $filename Filename
   */
  public function loadFile($path, $filename=null) {
    $this->data = file_get_contents($path);
    if (empty($filename)) $filename = basename($path);
    $this->setFilename($filename);

    // Load MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $this->mime = finfo_file($finfo, $path);
    finfo_close($finfo);
  }


  /**
   * Set filename
   * @param string $filename Filename
   */
  public function setFilename($filename) {
    $this->filename = $filename;
  }


  /**
   * Get data
   * @return string Data
   */
  public function getData() {
    return $this->data;
  }


  /**
   * Get filename
   * @return string Filename
   */
  public function getFilename() {
    return $this->filename;
  }


  /**
   * Get MIME type
   * @return string MIME type
   */
  public function getMimeType() {
    return $this->mime;
  }

}
