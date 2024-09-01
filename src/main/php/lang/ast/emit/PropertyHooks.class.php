<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  Block,
  InstanceExpression,
  InvokeExpression,
  Literal,
  Method,
  OffsetExpression,
  Parameter,
  ReturnStatement,
  ScopeExpression,
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
  use VisibilityChecks;

  protected function rewriteHook($node, $name, $virtual, $literal) {

    // Magic constant referencing property name
    if ($node instanceof Literal && '__PROPERTY__' === $node->expression) return $literal;

    // Rewrite $this->propertyName to virtual property
    if (
      $node instanceof InstanceExpression &&
      $node->expression instanceof Variable && 'this' === $node->expression->pointer &&
      $node->member instanceof Literal && $name === $node->member->expression
    ) return $virtual;

    // <T>::$field::hook() => <T>::__<hook>_<field>()
    if (
      $node instanceof ScopeExpression &&
      $node->member instanceof InvokeExpression &&
      $node->member->expression instanceof Literal &&
      $node->type instanceof ScopeExpression &&
      $node->type->member instanceof Variable &&
      is_string($node->type->type) &&
      is_string($node->type->member->pointer)
    ) {
      return new ScopeExpression($node->type->type, new InvokeExpression(
        new Literal('__'.$node->member->expression->expression.'_'.$node->type->member->pointer),
        $node->member->arguments
      ));
    }

    foreach ($node->children() as &$child) {
      $child= $this->rewriteHook($child, $name, $virtual, $literal);
    }
    return $node;
  }

  protected function withScopeCheck($modifiers, $nodes) {
    if ($modifiers & MODIFIER_PRIVATE) {
      return new Block([$this->private('$name', 'access private'), ...$nodes]);
    } else if ($modifiers & MODIFIER_PROTECTED) {
      return new Block([$this->protected('$name', 'access protected'), ...$nodes]);
    } else if (1 === sizeof($nodes)) {
      return $nodes[0];
    } else {
      return new Block($nodes);
    }
  }

  protected function emitProperty($result, $property) {
    static $lookup= [
      'public'         => MODIFIER_PUBLIC,
      'protected'      => MODIFIER_PROTECTED,
      'private'        => MODIFIER_PRIVATE,
      'static'         => MODIFIER_STATIC,
      'final'          => MODIFIER_FINAL,
      'abstract'       => MODIFIER_ABSTRACT,
      'readonly'       => MODIFIER_READONLY,
      'public(set)'    => 0x1000000,
      'protected(set)' => 0x0000800,
      'private(set)'   => 0x0001000,
    ];

    // Emit XP meta information for the reflection API
    $scope= $result->codegen->scope[0];
    $modifiers= 0;
    foreach ($property->modifiers as $name) {
      $modifiers|= $lookup[$name];
    }

    // Derive modifiers for private(set) and protected(set), folding declarations
    // like `[visibility] [visibility](set)` to just the visibility itself.
    if ($modifiers & 0x1000000) {
      $check= null;
      $modifiers&= ~0x1000000;
      $write= MODIFIER_PUBLIC;
    } else if ($modifiers & 0x0000800) {
      $modifiers & MODIFIER_PROTECTED && $modifiers&= ~0x0000800;
      $write= MODIFIER_PROTECTED;
    } else if ($modifiers & 0x0001000) {
      $modifiers & MODIFIER_PRIVATE && $modifiers&= ~0x0001000;
      $write= MODIFIER_PRIVATE;
    } else {
      $write= $modifiers;
    }

    $scope->meta[self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => ['interface' === $scope->type->kind ? $modifiers | MODIFIER_ABSTRACT : $modifiers]
    ];

    $literal= new Literal("'{$property->name}'");
    $virtual= new InstanceExpression(new Variable('this'), new OffsetExpression(new Literal('__virtual'), $literal));

    // Emit get and set hooks in-place. Ignore any unknown hooks
    $get= $set= null;
    foreach ($property->hooks as $type => $hook) {
      $method= '__'.$type.'_'.$property->name;
      $modifierList= $modifiers & MODIFIER_ABSTRACT ? ['abstract'] : $hook->modifiers;
      if ('get' === $type) {
        $this->emitOne($result, new Method(
          $modifierList,
          $method,
          new Signature([], null, $hook->byref),
          null === $hook->expression ? null : [$this->rewriteHook(
            $hook->expression instanceof Block ? $hook->expression : new ReturnStatement($hook->expression),
            $property->name,
            $virtual,
            $literal
          )],
          null // $hook->annotations
        ));
        $get= $this->withScopeCheck($modifiers, [
          new Assignment(new Variable('r'), $hook->byref ? '=&' : '=', new InvokeExpression(
            new InstanceExpression(new Variable('this'), new Literal($method)),
            []
          )),
          new ReturnStatement(new Variable('r'))
        ]);
      } else if ('set' === $type) {
        $this->emitOne($result, new Method(
          $modifierList,
          $method,
          new Signature($hook->parameter ? [$hook->parameter] : [new Parameter('value', null)], null),
          null === $hook->expression ? null : [$this->rewriteHook(
            $hook->expression instanceof Block ? $hook->expression : new Assignment($virtual, '=', $hook->expression),
            $property->name,
            $virtual,
            $literal
          )],
          null // $hook->annotations
        ));
        $set= $this->withScopeCheck($write, [new InvokeExpression(
          new InstanceExpression(new Variable('this'), new Literal($method)),
          [new Variable('value')]
        )]);
      }
    }

    // Declare virtual properties with __set and __get as well as initializations
    // except inside interfaces, which cannot contain properties.
    if ('interface' === $scope->type->kind) return;

    $scope->virtual[$property->name]= [
      $get ?? new ReturnStatement($virtual),
      $set ?? new Assignment($virtual, '=', new Variable('value'))
    ];
    if (isset($property->expression)) {
      $scope->init[sprintf('$this->__virtual["%s"]', $property->name)]= $property->expression;
    }
  }
}