<?php namespace lang\ast\emit;

use lang\ast\types\{IsUnion, IsIntersection, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral, IsGeneric};

/**
 * PHP 7.4 syntax
 *
 * @see  https://wiki.php.net/rfc#php_74
 */
class PHP74 extends PHP {
  use
    ArbitrayNewExpressions,
    ArrayUnpackUsingMerge,
    AttributesAsComments,
    CallablesAsClosures,
    ChainScopeOperators,
    EmulatePipelines,
    MatchAsTernaries,
    NonCapturingCatchVariables,
    NullsafeAsTernaries,
    OmitArgumentNames,
    OmitConstantTypes,
    ReadonlyClasses,
    RewriteBlockLambdaExpressions,
    RewriteCloneWith,
    RewriteEnums,
    RewriteExplicitOctals,
    RewriteProperties,
    RewriteStaticVariableInitializations,
    RewriteThrowableExpressions
  ;

  public $targetVersion= 70400;

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
}