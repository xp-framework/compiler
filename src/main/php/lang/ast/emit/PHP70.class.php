<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\{Expression, InstanceExpression, ScopeExpression, Literal, Variable};
use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral, IsGeneric};

/**
 * PHP 7.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_70
 */
class PHP70 extends PHP {
  use 
    ArbitrayNewExpressions,
    ArrayUnpackUsingMerge,
    AttributesAsComments,
    ForeachDestructuringAsStatement,
    MatchAsTernaries,
    NullsafeAsTernaries,
    OmitArgumentNames,
    OmitConstModifiers,
    OmitConstantTypes,
    OmitPropertyTypes,
    ReadonlyClasses,
    RewriteAssignments,
    RewriteClassOnObjects,
    RewriteEnums,
    RewriteExplicitOctals,
    RewriteLambdaExpressions,
    RewriteMultiCatch,
    RewriteProperties,
    RewriteThrowableExpressions
  ;

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
        static $rewrite= [
          'object'   => 1,
          'void'     => 1,
          'iterable' => 1,
          'mixed'    => 1,
          'null'     => 1,
          'never'    => 1,
          'true'     => 'bool',
          'false'    => 'bool',
        ];

        $l= $t->literal();
        return (1 === ($r= $rewrite[$l] ?? $l)) ? null : $r;
      },
      IsGeneric::class      => function($t) { return null; }
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
      } else if ($callable->expression->member instanceof Expression) {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member->inline);
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
      } else if ($callable->expression->member instanceof Expression) {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member->inline);
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

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {

      // Rewrite null-coalesce operator
      $this->emitAssign($result, $assignment->variable);
      $result->out->write('??');
      $this->emitOne($result, $assignment->variable);
      $result->out->write('=');
      $this->emitOne($result, $assignment->expression);
      return;
    } else if ('array' === $assignment->variable->kind) {

      // Rewrite destructuring unless assignment consists only of variables
      foreach ($assignment->variable->values as $pair) {
        if (null === $pair[0] && (null === $pair[1] || $pair[1] instanceof Variable)) continue;
        return $this->rewriteDestructuring($result, $assignment);
      }
    }

    return parent::emitAssignment($result, $assignment);
  }
}