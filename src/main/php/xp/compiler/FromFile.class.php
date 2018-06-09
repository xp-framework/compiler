<?php namespace xp\compiler;

use io\File;
use io\Path;

/** A single source file */
class FromFile extends Input {
  private $file;

  /**
   * Creates a new instance
   *
   * @param string|io.File $file
   */
  public function __construct($file) {
    $this->file= $file instanceof File ? $file : new File($file);
  }

  /** @return iterable */
  public function getIterator() {
    yield new Path($this->file->getURI()) => $this->file->in();
  }
}