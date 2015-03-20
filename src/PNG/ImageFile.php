<?php

namespace PNG;

class ImageFile extends Image
{
  private $_filename;

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

    $this->_filename = $filename;

    parent::__construct();

    $this->setContents($contents);
  }

  public function getFilename()
  {
    return $this->_filename;
  }

  public function setFilename($filename)
  {
    $this->_filename = $filename;
  }

  public function save()
  {
    $result = file_put_contents($this->_filename, $this->getContents());

    if ($result === FALSE)
    {
      throw new \Exception('Could not write to file: ' . $this->_filename);
    }
  }
}
