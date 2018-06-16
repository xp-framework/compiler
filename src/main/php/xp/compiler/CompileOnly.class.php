<?php namespace xp\compiler;

use io\streams\OutputStream;

class CompileOnly extends Output {

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name) {
    return newinstance(OutputStream::class, [], [
      'write' => function($bytes) { },
      'flush' => function() { },
      'close' => function() { }
    ]);
  }
}