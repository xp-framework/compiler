<?php namespace xp\compiler;

use lang\IllegalArgumentException;
use util\cmd\Console;

abstract class Input implements \IteratorAggregate {

  /**
   * Returns input from the command line argument
   *
   * @param  string $arg
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public static function newInstance($arg) {
    if ('-' === $arg) {
      return new FromStream(Console::$in->getStream(), '-');
    } else if (is_file($arg)) {
      return new FromFile($arg);
    } else if (is_dir($arg)) {
      return new FromFilesIn($arg);
    } else {
      throw new IllegalArgumentException('Expecting either - for standard input, a file or a folder as input');
    }
  }
}