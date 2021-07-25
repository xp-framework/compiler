<?php namespace xp\compiler;

use Traversable;
use io\{File, Path};

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
  public function getIterator(): Traversable {
    yield new Path($this->file->getFileName()) => $this->file->in();
  }
}