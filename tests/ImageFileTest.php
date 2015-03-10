<?php

namespace UoMCS\PNG;

class ImageFileTest extends \PHPUnit_Framework_TestCase
{
  const TEST_CHANGED_IMAGE_PATH = '/../data/test2.png';
  const TEST_IMAGE_PATH = '/../data/test1.png';

  public function testConstructor()
  {
    $png = new ImageFile(__DIR__ . self::TEST_IMAGE_PATH);
    $this->assertInstanceOf('UoMCS\\PNG\\ImageFile', $png);
  }

  public function testAddChunk()
  {
    $png = new ImageFile(__DIR__ . self::TEST_IMAGE_PATH);
    $png->addITXtChunk('openbadges', 'json', '{}');

    $png->setFilename(__DIR__ . self::TEST_CHANGED_IMAGE_PATH);
    $png->save();
  }
}
