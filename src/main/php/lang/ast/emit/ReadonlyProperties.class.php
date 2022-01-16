<?php namespace lang\ast\emit;

use lang\ast\{Code, Error, Errors};

/**
 * Creates __get() and __set() overloads for readonly properties
 *
 * @see  https://github.com/xp-framework/compiler/issues/115
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 */
trait ReadonlyProperties {

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

    if (!in_array('readonly', $property->modifiers)) return parent::emitProperty($result, $property);

    $modifiers= 0;
    foreach ($property->modifiers as $name) {
      $modifiers|= $lookup[$name];
    }
    $result->meta[0][self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => [$modifiers]
    ];

    // Add visibility check for accessing private and protected properties
    if (in_array('private', $property->modifiers)) {
      $check= (
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot access private property ".__CLASS__."::\\$%1$s");'
      );
    } else if (in_array('protected', $property->modifiers)) {
      $check= (
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && !is_subclass_of($scope, __CLASS__) && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot access protected property ".__CLASS__."::\\$%1$s");'
      );
    } else {
      $check= '';
    }

    // Create virtual property implementing the readonly semantics
    $result->locals[2][$property->name]= [
      new Code(sprintf($check.'return $this->__virtual["%1$s"][0] ?? null;', $property->name)),
      new Code(sprintf(
        ($check ?: '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;').
        'if (isset($this->__virtual["%1$s"])) throw new \\Error("Cannot modify readonly property ".__CLASS__."::{$name}");'.
        'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot initialize readonly property ".__CLASS__."::{$name} from ".($scope ? "scope {$scope}": "global scope"));'.
        '$this->__virtual["%1$s"]= [$value];',
        $property->name
      )),
    ];
  }
}