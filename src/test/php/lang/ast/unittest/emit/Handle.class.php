<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

class Handle implements \IDisposable {
  public static $called= [];
  private $id;

  public function __construct($id) { $this->id= $id; }

  public function read($bytes= 8192) {
    self::$called[]= 'read@'.$this->id;

    if ($bytes <= 0) {
      throw new IllegalArgumentException('Cannot read '.$bytes.' bytes');
    }
    return 'test';
  }

  public function __dispose() {
    self::$called[]= '__dispose@'.$this->id;
  }
}