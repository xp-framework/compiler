<?php namespace lang\ast\emit;

trait FinalProperties {

  protected function emitProperty($result, $property) {
    static $lookup= [
      'public'    => MODIFIER_PUBLIC,
      'protected' => MODIFIER_PROTECTED,
      'private'   => MODIFIER_PRIVATE,
      'static'    => MODIFIER_STATIC,
      'final'     => MODIFIER_FINAL,
      'abstract'  => MODIFIER_ABSTRACT,
      'readonly'  => 0x0080, // XP 10.13: MODIFIER_READONLY
    ];

    $modifiers= 0;
    foreach ($property->modifiers as $name) {
      $modifiers|= $lookup[$name];
    }

    // Emit without final modifier, store `final` in xp::$meta
    $property->modifiers= array_diff($property->modifiers, ['final']);
    parent::emitProperty($result, $property);
    $result->codegen->scope[0]->meta[self::PROPERTY][$property->name][DETAIL_ARGUMENTS]= [MODIFIER_FINAL];
  }
}