<?php namespace lang\ast\emit;

use ReflectionProperty;
use lang\ast\Code;
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
      $check= (
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot access private property ".__CLASS__."::".$name);'
      );
    } else if ($modifiers & MODIFIER_PROTECTED) {
      $check= (
        '$scope= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["class"] ?? null;'.
        'if (__CLASS__ !== $scope && !is_subclass_of($scope, __CLASS__) && \\lang\\VirtualProperty::class !== $scope)'.
        'throw new \\Error("Cannot access protected property ".__CLASS__."::".$name);'
      );
    } else if (1 === sizeof($nodes)) {
      return $nodes[0];
    } else {
      return new Block($nodes);
    }

    return new Block([new Code($check), ...$nodes]);
  }

  protected function emitEmulatedHooks($result, $property) {
    static $lookup= [
      'public'    => MODIFIER_PUBLIC,
      'protected' => MODIFIER_PROTECTED,
      'private'   => MODIFIER_PRIVATE,
      'static'    => MODIFIER_STATIC,
      'final'     => MODIFIER_FINAL,
      'abstract'  => MODIFIER_ABSTRACT,
      'readonly'  => 0x0080, // XP 10.13: MODIFIER_READONLY
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
        $set= $this->withScopeCheck($modifiers, [new InvokeExpression(
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

  protected function emitNativeHooks($result, $property) {
    $result->codegen->scope[0]->meta[self::PROPERTY][$property->name]= [
      DETAIL_RETURNS     => $property->type ? $property->type->name() : 'var',
      DETAIL_ANNOTATIONS => $property->annotations,
      DETAIL_COMMENT     => $property->comment,
      DETAIL_TARGET_ANNO => [],
      DETAIL_ARGUMENTS   => []
    ];

    $property->comment && $this->emitOne($result, $property->comment);
    $property->annotations && $this->emitOne($result, $property->annotations);
    $result->at($property->declared)->out->write(implode(' ', $property->modifiers).' '.$this->propertyType($property->type).' $'.$property->name);
    if (isset($property->expression)) {
      if ($this->isConstant($result, $property->expression)) {
        $result->out->write('=');
        $this->emitOne($result, $property->expression);
      } else if (in_array('static', $property->modifiers)) {
        $result->codegen->scope[0]->statics['self::$'.$property->name]= $property->expression;
      } else {
        $result->codegen->scope[0]->init['$this->'.$property->name]= $property->expression;
      }
    }

    // TODO move this to lang.ast.emit.PHP once https://github.com/php/php-src/pull/13455 is merged
    $result->out->write('{');
    foreach ($property->hooks as $type => $hook) {
      $hook->byref && $result->out->write('&');
      $result->out->write($type);
      if ($hook->parameter) {
        $result->out->write('(');
        $this->emitOne($result, $hook->parameter);
        $result->out->write(')');
      }

      if (null === $hook->expression) {
        $result->out->write(';');
      } else if ($hook->expression instanceof Block) {
        $this->emitOne($result, $hook->expression);
      } else {
        $result->out->write('=>');
        $this->emitOne($result, $hook->expression);
        $result->out->write(';');
      }
    }
    $result->out->write('}');
  }

  protected function emitProperty($result, $property) {
    static $hooks= null;

    if (empty($property->hooks)) {
      parent::emitProperty($result, $property);
    } else if ($hooks ?? $hooks= method_exists(ReflectionProperty::class, 'getHooks')) {
      $this->emitNativeHooks($result, $property);
    } else {
      $this->emitEmulatedHooks($result, $property);
    }
  }
}