<?php namespace lang\ast\emit;

use ReflectionProperty;
use lang\ast\types\{
  IsArray,
  IsFunction,
  IsGeneric,
  IsIntersection,
  IsLiteral,
  IsMap,
  IsNullable,
  IsUnion,
  IsValue
};

/**
 * PHP 8.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_84
 */
class PHP84 extends PHP {
  use RewriteBlockLambdaExpressions;
  use PropertyHooks, AsymmetricVisibility {
    PropertyHooks::emitProperty as emitPropertyHooks;
    AsymmetricVisibility::emitProperty as emitAsymmetricVisibility;
  }

  public $targetVersion= 80400;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsFunction::class     => function($t) { return 'callable'; },
      IsValue::class        => function($t) { return $t->literal(); },
      IsNullable::class     => function($t) {
        if (null === ($l= $this->literal($t->element))) return null;
        return $t->element instanceof IsUnion ? $l.'|null' : '?'.$l;
      },
      IsIntersection::class => function($t) {
        $i= '';
        foreach ($t->components as $component) {
          if (null === ($l= $this->literal($component))) return null;
          $i.= '&'.$l;
        }
        return substr($i, 1);
      },
      IsUnion::class        => function($t) {
        $u= '';
        foreach ($t->components as $component) {
          if (null === ($l= $this->literal($component))) return null;
          $u.= '|'.$l;
        }
        return substr($u, 1);
      },
      IsLiteral::class      => function($t) { return $t->literal(); },
      IsGeneric::class      => function($t) { return null; }
    ];
  }

  protected function emitProperty($result, $property) {
    static $asymmetric= null;
    static $hooks= null;

    // TODO Remove once https://github.com/php/php-src/pull/15063 and
    // https://github.com/php/php-src/pull/13455 are merged
    if (
      !($asymmetric ?? $asymmetric= method_exists(ReflectionProperty::class, 'isPrivateSet')) &&
      array_intersect($property->modifiers, ['private(set)', 'protected(set)', 'public(set)'])
    ) {
      return $this->emitAsymmetricVisibility($result, $property);
    } else if (
      !($hooks ?? $hooks= method_exists(ReflectionProperty::class, 'getHooks')) &&
      $property->hooks
    ) {
      return $this->emitPropertyHooks($result, $property);
    }

    parent::emitProperty($result, $property);
  }
}