<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 8.1 syntax
 *
 * @see  https://wiki.php.net/rfc#php_81
 */
class PHP81 extends PHP {
  use RewriteBlockLambdaExpressions;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsFunction::class => function($t) { return 'callable'; },
      IsValue::class    => function($t) { return $t->literal(); },
      IsNullable::class => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class    => function($t) {
        $u= '';
        foreach ($t->components as $component) {
          if (null === ($l= $this->literal($component))) return null;
          $u.= '|'.$l;
        }
        return substr($u, 1);
      },
      IsLiteral::class  => function($t) { return $t->literal(); }
    ];
  }

  protected function emitArguments($result, $arguments) {
    $i= 0;
    foreach ($arguments as $name => $argument) {
      if ($i++) $result->out->write(',');
      if (is_string($name)) $result->out->write($name.':');
      $this->emitOne($result, $argument);
    }
  }

  protected function emitEnumCase($result, $case) {

    // TODO: Once enum PR is merged, remove this conditional and refactor the
    // code into a `RewriteEnums` trait to be included for all other versions
    if (Type::$ENUMS) {
      $result->out->write('case '.$case->name);
      if ($case->expression) {
        $result->out->write('=');
        $this->emitOne($result, $case->expression);
      }
      $result->out->write(';');
    } else {
      parent::emitEnumCase($result, $case);
    }
  }

  protected function emitEnum($result, $enum) {

    // TODO: Once enum PR is merged, remove this conditional and refactor the
    // code into a `RewriteEnums` trait to be included for all other versions
    if (Type::$ENUMS) {
      array_unshift($result->type, $enum);
      array_unshift($result->meta, []);
      $result->locals= [[], []];

      $result->out->write('enum '.$this->declaration($enum->name));
      $enum->base && $result->out->write(':'.$enum->base);
      $enum->implements && $result->out->write(' implements '.implode(', ', $enum->implements));
      $result->out->write('{');

      foreach ($enum->body as $member) {
        $this->emitOne($result, $member);
      }

      // Initializations
      $result->out->write('static function __init() {');
      $this->emitInitializations($result, $result->locals[0]);
      $this->emitMeta($result, $enum->name, $enum->annotations, $enum->comment);
      $result->out->write('}} '.$enum->name.'::__init();');
      array_shift($result->type);
    } else {
      parent::emitEnum($result, $enum);
    }
  }

  protected function emitNew($result, $new) {
    if ($new->type instanceof Node) {
      $result->out->write('new (');
      $this->emitOne($result, $new->type);
      $result->out->write(')(');
    } else {
      $result->out->write('new '.$new->type.'(');
    }

    $this->emitArguments($result, $new->arguments);
    $result->out->write(')');
  }

  protected function emitThrowExpression($result, $throw) {
    $result->out->write('throw ');
    $this->emitOne($result, $throw->expression);
  }

  protected function emitCatch($result, $catch) {
    $capture= $catch->variable ? ' $'.$catch->variable : '';
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable'.$capture.') {');
    } else {
      $result->out->write('catch('.implode('|', $catch->types).$capture.') {');
    }
    $this->emitAll($result, $catch->body);
    $result->out->write('}');
  }

  protected function emitNullsafeInstance($result, $instance) {
    $this->emitOne($result, $instance->expression);
    $result->out->write('?->');

    if ('literal' === $instance->member->kind) {
      $result->out->write($instance->member->expression);
    } else {
      $result->out->write('{');
      $this->emitOne($result, $instance->member);
      $result->out->write('}');
    }
  }

  protected function emitMatch($result, $match) {
    if (null === $match->expression) {
      $result->out->write('match (true) {');
    } else {
      $result->out->write('match (');
      $this->emitOne($result, $match->expression);
      $result->out->write(') {');
    }

    foreach ($match->cases as $case) {
      $b= 0;
      foreach ($case->expressions as $expression) {
        $b && $result->out->write(',');
        $this->emitOne($result, $expression);
        $b++;
      }
      $result->out->write('=>');
      $this->emitAsExpression($result, $case->body);
      $result->out->write(',');
    }

    if ($match->default) {
      $result->out->write('default=>');
      $this->emitAsExpression($result, $match->default);
    }

    $result->out->write('}');
  }
}