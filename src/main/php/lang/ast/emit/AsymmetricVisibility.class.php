<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  Block,
  InstanceExpression,
  Literal,
  OffsetExpression,
  ReturnStatement,
  Variable
};

/**
 * Asymmetric Visibility
 *
 * @see  https://wiki.php.net/rfc/asymmetric-visibility-v2
 * @test lang.ast.unittest.emit.AsymmetricVisibilityTest
 */
trait AsymmetricVisibility {
  use VisibilityChecks;

  protected function emitProperty($result, $property) {
    static $lookup= [
      'public'         => MODIFIER_PUBLIC,
      'protected'      => MODIFIER_PROTECTED,
      'private'        => MODIFIER_PRIVATE,
      'static'         => MODIFIER_STATIC,
      'final'          => MODIFIER_FINAL,
      'abstract'       => MODIFIER_ABSTRACT,
      'readonly'       => MODIFIER_READONLY,
      'public(set)'    => 0x0400,
      'protected(set)' => 0x0800,
      'private(set)'   => 0x1000,
    ];

    // Emit XP meta information for the reflection API
    $scope= $result->codegen->scope[0];
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

    $checks= [];
    if ($modifiers & 0x1000) {
      $checks[]= $this->private($property->name, 'modify private(set)');
    } else if ($modifiers & 0x0800) {
      $checks[]= $this->protected($property->name, 'modify protected(set)');
    }

    // The readonly flag is really two flags in one: write-once and restricted(set)
    if (in_array('readonly', $property->modifiers)) {
      $checks[]= $this->initonce($property->name);
    }

    $virtual= new InstanceExpression(new Variable('this'), new OffsetExpression(
      new Literal('__virtual'),
      new Literal("'{$property->name}'"))
    );
    $scope->virtual[$property->name]= [
      new ReturnStatement($virtual),
      new Block([...$checks, new Assignment($virtual, '=', new Variable('value'))]),
    ];
    if (isset($property->expression)) {
      $scope->init[sprintf('$this->__virtual["%s"]', $property->name)]= $property->expression;
    }
  }
}