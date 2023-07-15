<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral, IsGeneric};

/**
 * PHP 7.3 syntax. Same as PHP 7.2 but supports list reference assignments
 * so only rewrites null-coalesce assignments.
 *
 * @see  https://wiki.php.net/rfc/list_reference_assignment
 * @see  https://wiki.php.net/rfc#php_73
 */
class PHP73 extends PHP {
  use 
    ArbitrayNewExpressions,
    ArrayUnpackUsingMerge,
    AttributesAsComments,
    CallablesAsClosures,
    CheckOverride,
    MatchAsTernaries,
    NonCapturingCatchVariables,
    NullsafeAsTernaries,
    OmitArgumentNames,
    OmitConstantTypes,
    OmitPropertyTypes,
    ReadonlyProperties,
    ReadonlyClasses,
    RewriteClassOnObjects,
    RewriteEnums,
    RewriteExplicitOctals,
    RewriteLambdaExpressions,
    RewriteStaticVariableInitializations,
    RewriteThrowableExpressions
  ;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class        => function($t) { return 'array'; },
      IsMap::class          => function($t) { return 'array'; },
      IsFunction::class     => function($t) { return 'callable'; },
      IsValue::class        => function($t) { $l= $t->literal(); return 'static' === $l ? 'self' : $l; },
      IsNullable::class     => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class        => function($t) { return null; },
      IsIntersection::class => function($t) { return null; },
      IsLiteral::class      => function($t) {
        static $rewrite= [
          'mixed'    => 1,
          'null'     => 1,
          'never'    => 'void',
          'true'     => 'bool',
          'false'    => 'bool',
        ];

        $l= $t->literal();
        return (1 === ($r= $rewrite[$l] ?? $l)) ? null : $r;
      },
      IsGeneric::class      => function($t) { return null; }
    ];
  }

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {
      $this->emitAssign($result, $assignment->variable);
      $result->out->write('??');
      $this->emitOne($result, $assignment->variable);
      $result->out->write('=');
      $this->emitOne($result, $assignment->expression);
    } else {
      parent::emitAssignment($result, $assignment);
    }
  }
}