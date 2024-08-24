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
    if (in_array('private(set)', $property->modifiers)) {
      $check= [$this->private($property->name, 'modify private(set)')];
    } else if (in_array('protected(set)', $property->modifiers)) {
      $check= [$this->protected($property->name, 'modify protected(set)')];
    } else {
      $check= [];
    }

    $virtual= new InstanceExpression(new Variable('this'), new OffsetExpression(
      new Literal('__virtual'),
      new Literal("'{$property->name}'"))
    );

    $scope= $result->codegen->scope[0];
    $scope->virtual[$property->name]= [
      new ReturnStatement($virtual),
      new Block([...$check, new Assignment($virtual, '=', new Variable('value'))]),
    ];
    if (isset($property->expression)) {
      $scope->init[sprintf('$this->__virtual["%s"]', $property->name)]= $property->expression;
    }
  }
}