<?php

namespace UoMCS\PNG;

class Image
{
  private $_contents;

  protected setContents($contents)
  {
    $this->_contents = $contents;
  }
}
