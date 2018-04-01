<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

class Handle implements \IDisposable {
  public static $called= [];

  public function read($bytes= 8192) {
    self::$called[]= 'read';

    if ($bytes <= 0) {
      throw new IllegalArgumentException('Cannot read '.$bytes.' bytes');
    }
    return 'test';
  }

  public function __dispose() {
    self::$called[]= '__dispose';
  }
}