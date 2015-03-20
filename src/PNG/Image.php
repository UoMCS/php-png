<?php

namespace PNG;

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

  // IHDR format (s11.2.2)
  const CHUNK_IHDR_FORMAT = 'Nwidth/Nheight/Cbitdepth/Ccolourtype/Ccompression/Cfilter/Cinterlace';

  private $_contents;
  private $_signature_standard;
  private $_chunks;
  private $_chunks_count;
  private $_size;
  private $_chunk_header_format;

  private $_metadata;

  public function __construct()
  {
    // PNG signature (s5.2)
    $this->_signature_standard = pack(self::SIGNATURE_PACK_FORMAT, 137, 80, 78, 71, 13, 10, 26, 10);
    $this->_chunk_header_format = self::CHUNK_LENGTH_FORMAT . 'length/' . self::CHUNK_TYPE_FORMAT . 'type';
  }

  private function validateSignature($signature)
  {
    return ($signature === $this->_signature_standard);
  }

  private function validateCRC($crc, $type, $data)
  {
    return ($crc == crc32($type . $data));
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

  public function getITXtChunksFromKey($search_key)
  {
    $matches = array();
    $search_type = 'iTXt';

    if (count($this->_chunks) >= 1)
    {
      foreach ($this->_chunks as $chunk)
      {
        if ($chunk['type'] == $search_type)
        {
          $key_length = strpos($chunk['data'], self::NULL_SEPARATOR);

          if ($key_length !== FALSE && $key_length > 0)
          {
            $chunk_key = substr($chunk['data'], 0, $key_length);
            if ($chunk_key == $search_key)
            {
              $matches[] = $chunk;
            }
          }
        }
      }
    }

    return $matches;
  }

  public function getWidth()
  {
    return $this->_metadata['width'];
  }

  public function getHeight()
  {
    return $this->_metadata['height'];
  }

  public function getBitDepth()
  {
    return $this->_metadata['bitdepth'];
  }

  public function getColourType()
  {
    return $this->_metadata['colourtype'];
  }

  public function getCompression()
  {
    return $this->_metadata['compression'];
  }

  public function getFilter()
  {
    return $this->_metadata['filter'];
  }

  public function getInterlace()
  {
    return $this->_metadata['interlace'];
  }

  public function getChunks()
  {
    return $this->_chunks;
  }

  protected function getContents()
  {
    return $this->_contents;
  }

  protected function setContents($contents)
  {
    $this->_chunks = array();
    $this->_chunks_count = 0;

    // First bytes of contents much match the signature
    $signature = substr($contents, 0, self::SIGNATURE_BYTES);

    if (!$this->validateSignature($signature))
    {
      throw new \Exception('Invalid image, signature (' . $signature . ') does not match signature standard (' . $this->_signature_standard . ')');
    }

    // Start off by skipping signature, read each chunk until the end
    $size = strlen($contents);
    $position = self::SIGNATURE_BYTES;

    do {
      // Grab chunk header (length + type)
      $chunk_header = unpack($this->_chunk_header_format, substr($contents, $position, self::CHUNK_HEADER_BYTES));
      $chunk_size = $chunk_header['length'] + self::CHUNK_OVERHEAD_BYTES;

      // Data starts after length + type (s5.3)
      $data_position = $position + self::CHUNK_HEADER_BYTES;
      $data_format = self::CHUNK_DATA_FORMAT . $chunk_header['length'];
      $raw_data = substr($contents, $data_position, $chunk_header['length']);
      $data = unpack($data_format, $raw_data);
      $data = implode($data);

      // CRC starts after length + type + data (s5.3)
      $crc_position = $position + self::CHUNK_HEADER_BYTES + $chunk_header['length'];
      $crc = unpack(self::CHUNK_CRC_FORMAT, substr($contents, $crc_position, self::CHUNK_CRC_BYTES));
      $crc = implode($crc);

      if (!$this->validateCRC($crc, $chunk_header['type'], $data))
      {
        throw new \Exception('Invalid CRC');
      }

      $chunk = array(
        'size' => $chunk_size,
        'type' => $chunk_header['type'],
        'data_size' => $chunk_header['length'],
        'data' => $data,
        'raw_data' => $raw_data,
        'crc' => $crc,
      );

      $this->_chunks[] = $chunk;
      $this->_chunks_count += 1;

      // Fetch next chunk
      $position += $chunk_size;
    } while ($position < $size);

    // Validate specific chunks and ordering
    // First chunk must be the header (s11.2.2)
    if (strtoupper($this->_chunks[0]['type']) != 'IHDR')
    {
      throw new \Exception('First chunk after signature was not IHDR');
    }

    // Last chunk must be IEND (s11.2.5)
    if (strtoupper($this->_chunks[$this->_chunks_count - 1]['type']) != 'IEND')
    {
      throw new \Exception('Last chunk was not IEND');
    }

    $this->_metadata = unpack(self::CHUNK_IHDR_FORMAT, $this->_chunks[0]['raw_data']);

    $this->_size = $size;
    $this->_contents = $contents;
  }
}
