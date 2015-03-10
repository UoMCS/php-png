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
  const CHUNK_LENGTH_FORMAT = 'N';

  const CHUNK_TYPE_BYTES = 4;
  const CHUNK_TYPE_FORMAT = 'a4';

  const CHUNK_CRC_BYTES = 4;
  const CHUNK_CRC_FORMAT = 'N';

  const CHUNK_DATA_FORMAT = 'a';

  const CHUNK_HEADER_BYTES = 8; // length + type
  const CHUNK_OVERHEAD_BYTES = 12; // length + type + CRC

  const MAX_KEYWORD_BYTES = 79;
  const NULL_SEPARATOR = "\0";
  const IEND_CHUNK_BYTES = 12;

  private $_contents;
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

  private function validateCRC($crc, $type, $data)
  {
    return ($crc == crc32($type . $data));
  }

  private function validateContents($contents)
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
      // Grab header (length + type)
      $chunk_header = unpack($this->_chunk_header_format, substr($contents, $position, self::CHUNK_HEADER_BYTES));
      $chunk_size = $chunk_header['length'] + self::CHUNK_OVERHEAD_BYTES;

      // Data starts after length + type (s5.3)
      $data_position = $position + self::CHUNK_HEADER_BYTES;
      $data_format = self::CHUNK_DATA_FORMAT . $chunk_header['length'];
      $data = unpack($data_format, substr($contents, $data_position, $chunk_header['length']));

      // CRC starts after length + type + data (s5.3)
      $crc_position = $position + self::CHUNK_HEADER_BYTES + $chunk_header['length'];
      $crc = unpack(self::CHUNK_CRC_FORMAT, substr($contents, $crc_position, self::CHUNK_CRC_BYTES));
      $crc = implode($crc);

      if (!$this->validateCRC($crc, $chunk_header['type'], implode($data)))
      {
        throw new \Exception('Invalid CRC');
      }

      // Fetch next chunk
      $position += $chunk_size;
    } while ($position < $size);
  }

  private function addChunk($type, $data)
  {
    $length = pack('N', strlen($data));
    $crc = pack('N', crc32($type . $data));

    $chunk = $length . $type . $data . $crc;
    $size = strlen($this->_contents);

    // Insert new chunk before IEND chunk
    $before_iend = substr($this->_contents, 0, $size - self::IEND_CHUNK_BYTES);
    $iend = substr($this->_contents, $size - self::IEND_CHUNK_BYTES);
    $contents = $before_iend . $chunk . $iend;

    $this->setContents($contents);
  }

  public function addITXtChunk($key, $language, $text)
  {
    if (strlen($key) > self::MAX_KEYWORD_BYTES)
    {
      throw new \Exception('Key length ' . strlen($key) . ' greater than maximum: ' . self::MAX_KEYWORD_BYTES);
    }

    // Build up data field
    $data = $key;
    $data .= self::NULL_SEPARATOR;
    $data .= self::NULL_SEPARATOR; // Compression flag
    $data .= self::NULL_SEPARATOR; // Compression method
    $data .= $language; // Language tag
    $data .= self::NULL_SEPARATOR;
    $data .= self::NULL_SEPARATOR;
    $data .= $text;

    $this->addChunk('iTXt', $data);
  }

  protected function getContents()
  {
    return $this->_contents;
  }

  protected function setContents($contents)
  {
    $this->validateContents($contents);
    $this->_contents = $contents;
  }
}
