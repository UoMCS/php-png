<?php

namespace UoMCS\PNG;

class Image
{
  private $_contents;

  protected function setContents($contents)
  {
    $this->_contents = $contents;
  }
}
