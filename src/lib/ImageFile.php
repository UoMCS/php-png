<?php

namespace UoMCS\PNG;

class ImageFile extends Image
{
  public function __construct($filename)
  {
    if (!file_exists($filename))
    {
      throw new Exception('File does not exist: ' . $filename);
    }

    if (!is_file($filename))
    {
      throw new Exception('Filename is not a file: ' . $filename);
    }

    if (!is_readable($filename))
    {
      throw new Exception('File is not readable: ' . $filename);
    }

    $contents = file_get_contents($filename);

    if ($contents === FALSE)
    {
      throw new Exception('Could not read file: ' . $filename);
    }

    $this->setContents($contents);
  }
}
