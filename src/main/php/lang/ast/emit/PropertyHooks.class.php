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

  protected function rewriteHook($node, $name, $virtual, $literal) {

    // Magic constant referencing property nae
    if ($node instanceof Literal && '__PROPERTY__' === $node->expression) return $literal;

    // Special variable $field, $this->propertyName syntax
    if ($node instanceof Variable && 'field' === $node->pointer || (
      $node instanceof InstanceExpression &&
      $node->expression instanceof Variable && 'this' === $node->expression->pointer &&
      $node->member instanceof Literal && $name === $node->member->expression
    )) return $virtual;

    foreach ($node->children() as &$child) {
      $child= $this->rewriteHook($child, $name, $virtual, $literal);
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
          new Signature([], null),
          null === $hook->expression ? null : [$this->rewriteHook(
            $hook->expression instanceof Block ? $hook->expression : new ReturnStatement($hook->expression),
            $property->name,
            $virtual,
            $literal
          )],
          $hook->annotations
        ));
        $get= new ReturnStatement(new InvokeExpression(
          new InstanceExpression(new Variable('this'), new Literal($method)),
          []
        ));
      } else if ('set' === $type) {
        $this->emitOne($result, new Method(
          $modifierList,
          $method,
          new Signature($hook->parameter ? [$hook->parameter] : [new Parameter('value', null)], null),
          null === $hook->expression ? null : [$this->rewriteHook(
            $hook->expression,
            $property->name,
            $virtual,
            $literal
          )],
          $hook->annotations
        ));
        $set= new InvokeExpression(
          new InstanceExpression(new Variable('this'), new Literal($method)),
          [new Variable('value')]
        );
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