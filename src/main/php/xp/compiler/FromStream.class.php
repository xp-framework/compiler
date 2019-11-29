<?php namespace xp\compiler;

use io\Path;

/** Streamed input */
class FromStream extends Input {
  private $stream, $name;

  /**
   * Creates a new instance
   *
   * @param io.streams.InputStream $file
   * @param string $name
   */
  public function __construct($stream, $name) {
    $this->stream= $stream;
    $this->name= $name;
  }

  /** @return iterable */
  public function getIterator() {
    yield new Path($this->name) => $this->stream;
  }
}