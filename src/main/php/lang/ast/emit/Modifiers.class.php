<?php namespace lang\ast\emit;

class Modifiers {
  const LOOKUP= [
    'public'         => MODIFIER_PUBLIC,
    'protected'      => MODIFIER_PROTECTED,
    'private'        => MODIFIER_PRIVATE,
    'static'         => MODIFIER_STATIC,
    'final'          => MODIFIER_FINAL,
    'abstract'       => MODIFIER_ABSTRACT,
    'readonly'       => 0x0080,    // XP 10.13: MODIFIER_READONLY
    'public(set)'    => 0x1000000,
    'protected(set)' => 0x0000800,
    'private(set)'   => 0x0001000,
  ];

  /**
   * Converts modifiers to a bit set
   *
   * @param  string[] $modifiers
   * @return int
   */
  public static function bits($modifiers) {
    $bits= 0;
    foreach ($modifiers as $name) {
      $bits|= self::LOOKUP[$name];
    }
    return $bits;
  }
}