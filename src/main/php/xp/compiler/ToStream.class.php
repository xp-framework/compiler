<?php namespace xp\compiler;

class ToStream extends Output {
  private $stream;

  /** @param io.streams.OutputStream $stream */
  public function __construct($stream) {
    $this->stream= $stream;
  }

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name) {
    return $this->stream;
  }
}