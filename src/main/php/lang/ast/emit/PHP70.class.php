<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\{InstanceExpression, ScopeExpression, NewExpression, NewClassExpression, UnpackExpression, Literal};
use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_70
 */
class PHP70 extends PHP {
  use OmitPropertyTypes, OmitConstModifiers, ReadonlyProperties;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteMultiCatch, RewriteClassOnObjects, RewriteExplicitOctals, RewriteEnums;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsFunction::class     => function($t) { return 'callable'; },
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsValue::class        => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class     => function($t) { return null; },
      IsUnion::class        => function($t) { return null; },
      IsIntersection::class => function($t) { return null; },
      IsLiteral::class      => function($t) {
        $l= $t->literal();
        return ('object' === $l || 'void' === $l || 'iterable' === $l || 'mixed' === $l || 'never' === $l) ? null : $l;
      },
    ];
  }

  protected function emitCallable($result, $callable) {
    $t= $result->temp();
    $result->out->write('(is_callable('.$t.'=');
    if ($callable->expression instanceof Literal) {

      // Rewrite f() => "f"
      $result->out->write('"'.trim($callable->expression->expression, '"\'').'"');
    } else if ($callable->expression instanceof InstanceExpression) {

      // Rewrite $this->f => [$this, "f"]
      $result->out->write('[');
      $this->emitOne($result, $callable->expression->expression);
      if ($callable->expression->member instanceof Literal) {
        $result->out->write(',"'.trim($callable->expression->member, '"\'').'"');
      } else {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member);
      }
      $result->out->write(']');
    } else if ($callable->expression instanceof ScopeExpression) {

      // Rewrite self::f => [self::class, "f"]
      $result->out->write('[');
      if ($callable->expression->type instanceof Node) {
        $this->emitOne($result, $callable->expression->type);
      } else {
        $result->out->write($callable->expression->type.'::class');
      }
      if ($callable->expression->member instanceof Literal) {
        $result->out->write(',"'.trim($callable->expression->member, '"\'').'"');
      } else {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member);
      }
      $result->out->write(']');
    } else {

      // Emit other expressions as-is
      $this->emitOne($result, $callable->expression);
    }

    // Emit equivalent of Closure::fromCallable() which doesn't exist until PHP 7.1
    $a= $result->temp();
    $result->out->write(')?function(...'.$a.') use('.$t.') { return '.$t.'(...'.$a.'); }:');
    $result->out->write('(function() { throw new \Error("Given argument is not callable"); })())');
  }
}