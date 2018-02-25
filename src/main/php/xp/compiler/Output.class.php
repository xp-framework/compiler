<?php namespace xp\compiler;

interface Output {

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name);

}