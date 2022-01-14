<?php namespace lang\ast\emit\php;

use lang\ast\Code;

/**
 * Creates __get() and __set() overloads which will create type-checked
 * instance properties for PHP < 7.4
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

    // Because PHP doesn't have __getStatic() and __setStatic(), we cannot simulate
    // property type checks for static members.
    if (null === $property->type || in_array('static', $property->modifiers)) {
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

    $result->locals[2][$property->name]= [
      new Code('return $this->__virtual["'.$property->name.'"];'),
      new Code(sprintf(
        $assign.'else throw new \\TypeError("Cannot assign ".typeof($value)." to property ".self::class."::\\$%1$s of type %2$s");',
        $property->name,
        $property->type->name()
      ))
    ];

    // Initialize via constructor
    if (isset($property->expression)) {
      $result->locals[1]['$this->'.$property->name]= $property->expression;
    }

    // Emit XP meta information for the reflection API
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
  }
}