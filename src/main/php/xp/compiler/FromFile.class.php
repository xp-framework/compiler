<?php namespace xp\compiler;

use io\File;
use io\Folder;
use io\Path;

/** A single source file */
class FromFile implements Input {
  private $file, $base;

  /**
   * Creates a new instance
   *
   * @param string|io.File $file
   * @param string|io.Folder $base
   */
  public function __construct($file, $base) {
    $this->file= $file instanceof File ? $file : new File($file);
    $this->base= $base instanceof Folder ? $base : new Folder($base);
  }

  /** @return iterable */
  public function getIterator() {
    $uri= (new Path($this->file->getURI()))->relativeTo($this->base)->toString();
    yield $uri => $this->file->in();
  }
}