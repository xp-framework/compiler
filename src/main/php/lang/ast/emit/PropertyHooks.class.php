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
 * @test lang.ast.unittest.emit.PropertyHooksTest
 */
trait PropertyHooks {

  protected function rewriteHook($node, $virtual, $literal) {
    if ($node instanceof Variable && 'field' === $node->pointer) return $virtual;
    if ($node instanceof Literal && '__PROPERTY__' === $node->expression) return $literal;

    foreach ($node->children() as &$child) {
      $child= $this->rewriteHook($child, $virtual, $literal);
    }
    return $node;
  }

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

    $scope= $result->codegen->scope[0];
    $literal= new Literal("'{$property->name}'");
    $virtual= new InstanceExpression(new Variable('this'), new OffsetExpression(new Literal('__virtual'), $literal));

    if ($hook= $property->hooks['set'] ?? null) {
      $set= new Block([$this->rewriteHook($hook->expression, $virtual, $literal)]);

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

    if ($hook= $property->hooks['get'] ?? null) {
      $get= $this->rewriteHook(
        $hook->expression instanceof Block ? $hook->expression : new ReturnStatement($hook->expression),
        $virtual,
        $literal
      );
    } else {
      $get= new ReturnStatement($virtual);
    }

    $scope->virtual[$property->name]= [$get, $set];
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