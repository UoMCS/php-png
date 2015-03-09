<?php

namespace UoMCS\PNG;

class ImageFileTest extends \PHPUnit_Framework_TestCase
{
  const TEST_IMAGE_PATH = '/../data/test1.png';

  public function testConstructor()
  {
    $png = new ImageFile(__DIR__ . self::TEST_IMAGE_PATH);
    $this->assertInstanceOf('UoMCS\\PNG\\ImageFile', $png);
  }
}
