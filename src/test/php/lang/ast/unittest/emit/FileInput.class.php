<?php namespace lang\ast\unittest\emit;

use lang\Closeable;
use lang\IllegalArgumentException;

class FileInput implements Closeable {
  public static $open= false;

  public function __construct($filename) {
    self::$open= true;
  }

  public function read($bytes= 8192) {
    if (!self::$open) {
      throw new IllegalArgumentException('Cannot read from closed file');
    }
    return 'test';
  }

  public function close() {
    self::$open= false;
  }
}