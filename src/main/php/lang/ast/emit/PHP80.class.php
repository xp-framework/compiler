<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap, IsNullable, IsValue, IsLiteral};

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends PHP {
  use RewriteBlockLambdaExpressions;

  /** Sets up type => literal mappings */
  public function __construct() {
    $this->literals= [
      IsFunction::class => function($t) { return null; },
      IsArray::class    => function($t) { return 'array'; },
      IsMap::class      => function($t) { return 'array'; },
      IsValue::class    => function($t) { return $t->literal(); },
      IsNullable::class => function($t) { $l= $this->literal($t->element); return null === $l ? null : '?'.$l; },
      IsUnion::class    => function($t) {
        $l= '';
        foreach ($t->components as $component) {
          $l.= '|'.$this->literal($component);
        }
        return substr($l, 1);
      },
      IsLiteral::class  => function($t) { return $t->literal(); }
    ];
  }

  protected function emitArguments($result, $arguments) {
    $s= sizeof($arguments) - 1;
    $i= 0;
    foreach ($arguments as $name => $argument) {
      if (is_string($name)) $result->out->write($name.':');
      $this->emitOne($result, $argument);
      if ($i++ < $s) $result->out->write(', ');
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

  protected function emitMatch($result, $match) {
    $result->out->write('match (');
    $this->emitOne($result, $match->expression);
    $result->out->write(') {');

    foreach ($match->cases as $case) {
      $b= 0;
      foreach ($case->expressions as $expression) {
        $b && $result->out->write(',');
        $this->emitOne($result, $expression);
        $b++;
      }
      $result->out->write('=>');
      $this->emitOne($result, $case->body);
      $result->out->write(',');
    }

    if ($match->default) {
      $result->out->write('default=>');
      $this->emitOne($result, $match->default);
    }

    $result->out->write('}');
  }
}