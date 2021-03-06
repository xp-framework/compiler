<?php namespace lang\ast\unittest\emit;

use lang\{IllegalArgumentException, Value};

/** Used by `UsingTest` */
class Handle implements Value, \IDisposable {
  public static $DEFAULT;
  public static $called= [];
  private $id;

  static function __static() {
    self::$DEFAULT= new Handle(0);
  }

  public function __construct($id) { $this->id= $id; }

  public function redirect($id) {
    $this->id= $id;
    return $this;
  }

  public function read($bytes= 8192) {
    self::$called[]= 'read@'.$this->id;

    if ($bytes <= 0) {
      throw new IllegalArgumentException('Cannot read '.$bytes.' bytes');
    }
    return 'test';
  }

  public function toString() { return nameof($this).'<'.$this->id.'>'; }

  public function hashCode() { return '#'.$this->id; }

  public function compareTo($value) { return $value instanceof self ? $value->id <=> $this->id : 1; }

  public function __dispose() {
    self::$called[]= '__dispose@'.$this->id;
  }
}