<?php namespace xp\compiler;

use util\cmd\Console;

abstract class Output {

  /**
   * Returns output from the command line argument
   *
   * @param  string $arg
   * @return self
   */
  public static function newInstance($arg) {
    if (null === $arg) {
      return new CompileOnly();
    } else if ('-' === $arg) {
      return new ToStream(Console::$out->stream());
    } else if (strstr($arg, '.php')) {
      return new ToFile($arg);
    } else if (strstr($arg, '.xar')) {
      return new ToArchive($arg);
    } else {
      return new ToFolder($arg);
    }
  }

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public abstract function target($name);

  /** @return void */
  public function close() {
    // NOOP
  }
}