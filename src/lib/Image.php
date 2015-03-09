<?php

namespace UoMCS\PNG;

/**
 * Top level Image class.
 *
 * Comments with (sX.Y) refer to relevant section of the specification.
 *
 */
class Image
{
  private $_contents;
  private $_signature;

  const SIGNATURE_LENGTH = 8;
  const SIGNATURE_PACK_FORMAT = 'C8';

  public function __construct()
  {
    // PNG signature (s5.2)
    $this->_signature = pack(self::SIGNATURE_PACK_FORMAT, 137, 80, 78, 71, 13, 10, 26, 10);
  }

  private function validateHeader($header)
  {
    return ($header === $this->_signature);
  }

  protected function getContents($contents)
  {
    return $_contents;
  }

  protected function setContents($contents)
  {
    // First bytes of contents much match the signature
    $header = substr($contents, 0, self::SIGNATURE_LENGTH);

    if (!$this->validateHeader($header))
    {
      throw new \Exception('Invalid image, header (' . $header . ') does not match signature (' . $this->_signature . ')');
    }

    $this->_contents = $contents;
  }
}
