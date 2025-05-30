<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  Block,
  InstanceExpression,
  InvokeExpression,
  Literal,
  OffsetExpression,
  ReturnStatement,
  Variable
};
use lang\ast\{Code, Error, Errors};

/**
 * Creates __get() and __set() overloads for readonly properties
 *
 * @see  https://github.com/xp-framework/compiler/issues/115
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 */
trait ReadonlyProperties {
  use VisibilityChecks;

  protected function emitProperty($result, $property) {
    $scope= $result->codegen->scope[0];
    $modifiers= Modifiers::bits($property->modifiers);
    $scope->meta[self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => [$modifiers]
    ];

    // Add visibility check for accessing private and protected properties
    if ($modifiers & MODIFIER_PRIVATE) {
      $check= $this->private($property->name, 'access private');
    } else if ($modifiers & MODIFIER_PROTECTED) {
      $check= $this->protected($property->name, 'access protected');
    } else {
      $check= null;
    }

    $virtual= new InstanceExpression(new Variable('this'), new OffsetExpression(
      new Literal('__virtual'),
      new Literal("'{$property->name}'"))
    );

    // Create virtual property implementing the readonly semantics
    $scope->virtual[$property->name]= [
      $check ? new Block([$check, new ReturnStatement($virtual)]) : new ReturnStatement($virtual),
      new Block([
        $check ?? new Code('$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'),
        $this->initonce($property->name),
        new Code(sprintf(
          'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
          'throw new \\Error("Cannot initialize readonly property ".__CLASS__."::{$name} from ".($scope ? "scope {$scope}": "global scope"));'.
          '$this->__virtual["%1$s"]= [$value];',
          $property->name
        )),
        new Assignment($virtual, '=', new Variable('value'))
      ]),
    ];
  }
}