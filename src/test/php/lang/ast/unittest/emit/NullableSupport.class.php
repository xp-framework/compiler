<?php namespace lang\ast\unittest\emit;

use lang\{Nullable, Type};

trait NullableSupport {

  /** Creates a nullable type if XP core supports it */
  private function nullable(Type $t): Type {
    static $support= null;

    return ($support ?? $support= class_exists(Nullable::class)) ? new Nullable($t) : $t;
  }
}
