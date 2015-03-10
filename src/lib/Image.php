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
  const SIGNATURE_BYTES = 8;
  const SIGNATURE_PACK_FORMAT = 'C8';

  const CHUNK_LENGTH_BYTES = 4;
  const CHUNK_LENGTH_FORMAT = 'L';

  const CHUNK_TYPE_BYTES = 4;
  const CHUNK_TYPE_FORMAT = 'a4';

  const CHUNK_CRC_BYTES = 4;

  const CHUNK_HEADER_BYTES = 8; // length + type
  const CHUNK_OVERHEAD_BYTES = 12; // length + type + CRC

  private $_contents;
  private $_size;
  private $_signature;
  private $_chunk_header_format;

  public function __construct()
  {
    // PNG signature (s5.2)
    $this->_signature = pack(self::SIGNATURE_PACK_FORMAT, 137, 80, 78, 71, 13, 10, 26, 10);
    $this->_chunk_header_format = self::CHUNK_LENGTH_FORMAT . 'length/' . self::CHUNK_TYPE_FORMAT . 'type';
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
    $header = substr($contents, 0, self::SIGNATURE_BYTES);

    if (!$this->validateHeader($header))
    {
      throw new \Exception('Invalid image, header (' . $header . ') does not match signature (' . $this->_signature . ')');
    }

    // Start off by skipping header, read each chunk until the end
    $size = strlen($contents);
    $position = self::SIGNATURE_BYTES;

    do {
      $chunk_header = unpack($this->_chunk_header_format, substr($contents, $position, self::CHUNK_HEADER_BYTES));
      $chunk_size = $chunk_header['length'] + self::CHUNK_OVERHEAD_BYTES;

      $position += $chunk_size;
    } while ($position < $size);


    $this->_contents = $contents;
    $this->_size = $size;
  }
}
