<?php namespace lang\ast\emit\php;

use lang\ast\Code;
use lang\ast\emit\InType;

/**
 * Creates __get() and __set() overloads which will create type-checked
 * instance properties for PHP < 7.4. Performs type coercion for scalar
 * types just as PHP would w/o strict type checks.
 *
 * Important: Because PHP doesn't have __getStatic() and __setStatic(),
 * we cannot simulate property type checks for static members!
 *
 * @see  https://wiki.php.net/rfc/typed_properties_v2
 */
trait VirtualPropertyTypes {

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

    // Exclude properties w/o type, static and readonly properties
    if (null === $property->type || in_array('static', $property->modifiers) || in_array('readonly', $property->modifiers)) {
      return parent::emitProperty($result, $property);
    }

    // Create virtual instance property implementing type coercion and checks
    switch ($property->type->name()) {
      case 'string':
        $assign= 'if (is_scalar($value)) $this->__virtual["%1$s"]= (string)$value;';
        break;
      case 'bool':
        $assign= 'if (is_scalar($value)) $this->__virtual["%1$s"]= (bool)$value;';
        break;
      case 'int':
        $assign= 'if (is_numeric($value) || is_bool($value)) $this->__virtual["%1$s"]= (int)$value;';
        break;
      case 'float':
        $assign= 'if (is_numeric($value) || is_bool($value)) $this->__virtual["%1$s"]= (float)$value;';
        break;
      default:
        $assign= 'if (is("%2$s", $value)) $this->__virtual["%1$s"]= $value;';
        break;
    }

    // Add visibility check for private and protected properties
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

    $scope= $result->codegen->scope[0];
    $scope->virtual[$property->name]= [
      new Code(sprintf($check.'return $this->__virtual["%1$s"];', $property->name)),
      new Code(sprintf(
        $check.$assign.
        'else throw new \\TypeError("Cannot assign ".(is_object($value) ? get_class($value) : gettype($value))." to property ".__CLASS__."::\\$%1$s of type %2$s");',
        $property->name,
        $property->type->name()
      ))
    ];

    // Initialize via constructor
    if (isset($property->expression)) {
      $scope->init["\$this->{$property->name}"]= $property->expression;
    }

    // Emit XP meta information for the reflection API
    $modifiers= 0;
    foreach ($property->modifiers as $name) {
      $modifiers|= $lookup[$name];
    }
    $scope->meta[self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => [$modifiers]
    ];
  }
}