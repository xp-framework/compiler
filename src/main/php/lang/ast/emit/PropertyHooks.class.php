<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  Block,
  Braced,
  ClosureExpression,
  InstanceExpression,
  InvokeExpression,
  Literal,
  OffsetExpression,
  ReturnStatement,
  Signature,
  Variable
};

/**
 * Property hooks
 *
 * @see  https://wiki.php.net/rfc/property-hooks
 */
trait PropertyHooks {

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

    if (empty($property->hooks)) return parent::emitProperty($result, $property);

    $virtual= new InstanceExpression(
      new Variable('this'),
      new OffsetExpression(new Literal('__virtual'), new Literal("'{$property->name}'"))
    );

    // Execute `set` hook, then assign virtual property to special variable $field
    if ($hook= $property->hooks['set'] ?? null) {
      $set= new Block([$hook->expression, new Assignment($virtual, '=', new Variable('field'))]);

      if ($hook->parameter) {
        if ('value' !== $hook->parameter->name) {
          array_unshift($set->statements, new Assignment(
            new Variable($hook->parameter->name),
            '=',
            new Variable('value')
          ));
        }

        // Perform type checking if parameter is typed using an IIFE
        if ($hook->parameter->type) {
          array_unshift($set->statements, new InvokeExpression(
            new Braced(new ClosureExpression(new Signature([$hook->parameter], null), [], [])),
            [new Variable('value')]
          ));
        }
      }
    } else {
      $set= new Assignment($virtual, '=', new Variable('value'));
    }

    // Assign special variable $field to virtual property, then execute `get` hook
    if ($hook= $property->hooks['get'] ?? null) {
      $get= new Block([
        new Assignment(new Variable('field'), '=', $virtual),
        $hook->expression instanceof Block ? $hook->expression : new ReturnStatement($hook->expression)
      ]);
    } else {
      $get= new ReturnStatement($virtual);
    }

    $scope= $result->codegen->scope[0];
    $scope->virtual[$property->name]= [$get, $set];

    // Initialize via constructor
    if (isset($property->expression)) {
      $scope->init[sprintf('$this->__virtual["%s"]', $property->name)]= $property->expression;
    }

    // Emit XP meta information for the reflection API
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
  }
}