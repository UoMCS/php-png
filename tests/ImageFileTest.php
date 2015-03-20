<?php

namespace PNG;

class ImageFileTest extends \PHPUnit_Framework_TestCase
{
  const TEST_CHANGED_IMAGE_PATH = '/../data/test2.png';
  const TEST_IMAGE_PATH = '/../data/test1.png';
  const TEST_ITXT_IMAGE_PATH = '/../data/test3.png';

  const TEST_IMAGE_WIDTH = 100;
  const TEST_IMAGE_HEIGHT = 100;
  const TEST_IMAGE_BIT_DEPTH = 8;
  const TEST_IMAGE_COLOUR_TYPE = 2;
  const TEST_IMAGE_COMPRESSION = 0;
  const TEST_IMAGE_FILTER = 0;
  const TEST_IMAGE_INTERLACE = 8;

  private function getTestPng()
  {
    return new ImageFile(__DIR__ . self::TEST_IMAGE_PATH);
  }

  public function testConstructor()
  {
    $png = $this->getTestPng();
    $this->assertInstanceOf('PNG\\ImageFile', $png);
  }

  public function testGetWidth()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_WIDTH, $png->getWidth());
  }

  public function testGetHeight()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_HEIGHT, $png->getHeight());
  }

  public function testGetBitDepth()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_BIT_DEPTH, $png->getBitDepth());
  }

  public function testGetCompression()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_COMPRESSION, $png->getCompression());
  }

  public function testGetFilter()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_FILTER, $png->getFilter());
  }

  public function testGetInterlace()
  {
    $png = $this->getTestPng();
    $this->assertEquals(self::TEST_IMAGE_INTERLACE, $png->getBitDepth());
  }

  public function testAddChunk()
  {
    $png = $this->getTestPng();
    $png->addITXtChunk('openbadges', 'json', '{}');

    $png->setFilename(__DIR__ . self::TEST_CHANGED_IMAGE_PATH);
    $png->save();
  }

  public function testAddChunkMaxKey()
  {
    $key = str_repeat('x', Image::MAX_KEYWORD_BYTES);

    $png = $this->getTestPng();
    $png->addITXtChunk($key, 'en', '');
  }

  /**
   * @expectedException Exception
   */
  public function testAddChunkOverMaxKey()
  {
    $key = str_repeat('x', Image::MAX_KEYWORD_BYTES + 1);

    $png = $this->getTestPng();
    $png->addITXtChunk($key, 'en', '');
  }

  public function testGetITXtChunkFromKeyMatch()
  {
    $png = new ImageFile(__DIR__ . self::TEST_ITXT_IMAGE_PATH);
    $matches = $png->getITXtChunksFromKey('openbadges');

    $this->assertCount(1, $matches);
  }

  public function testGetITXtChunkFromKeyNoMatch()
  {
    $png = $this->getTestPng();
    $matches = $png->getITXtChunksFromKey('openbadges');

    $this->assertCount(0, $matches);
  }
}
