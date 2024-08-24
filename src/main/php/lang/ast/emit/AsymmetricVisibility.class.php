<?php namespace lang\ast\emit;

use lang\ast\Code;
use lang\ast\nodes\{
  Assignment,
  Block,
  InstanceExpression,
  Literal,
  OffsetExpression,
  ReturnStatement,
  Variable
};

trait AsymmetricVisibility {

  protected function emitProperty($result, $property) {
    if (in_array('private(set)', $property->modifiers)) {
      $check= [new Code(
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot modify private(set) property ".__CLASS__."::\$".$name." from ".($scope ? "scope ".$scope : "global scope"));'
      )];
    } else if (in_array('protected(set)', $property->modifiers)) {
      $check= [new Code(
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && !is_subclass_of($scope, __CLASS__) && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot modify protected(set) property ".__CLASS__."::\$".$name." from ".($scope ? "scope ".$scope : "global scope"));'
      )];
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