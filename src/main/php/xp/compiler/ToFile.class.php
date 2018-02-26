<?php namespace xp\compiler;

use io\File;

class ToFile extends ToFileSystem {
  private $file;

  /** @param string $file */
  public function __construct($file) {
    $this->file= $file instanceof File ? $file : new File($file);
  }

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name) {
    $this->ensure($this->file->path);
    return $this->file->out();
  }
}