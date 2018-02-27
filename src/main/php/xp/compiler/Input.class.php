<?php namespace xp\compiler;

use lang\IllegalArgumentException;
use util\cmd\Console;

abstract class Input implements \IteratorAggregate {

  /**
   * Returns input from the command line argument
   *
   * @param  string $arg
   * @param  string $base
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public static function newInstance($arg, $base) {
    if ('-' === $arg) {
      return new FromStream(Console::$in->getStream(), '(standard input)');
    } else if (is_file($arg)) {
      return new FromFile($arg, $base ?: '.');
    } else if (is_dir($arg)) {
      return new FromFilesIn($arg, $base ?: $arg);
    } else {
      throw new IllegalArgumentException('Expecting either - for standard input, a file or a folder as input');
    }
  }
}