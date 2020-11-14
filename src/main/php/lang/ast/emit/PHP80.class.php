<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\types\{IsUnion, IsFunction, IsArray, IsMap};

/**
 * PHP 8.0 syntax
 *
 * @see  https://wiki.php.net/rfc#php_80
 */
class PHP80 extends PHP {
  use RewriteBlockLambdaExpressions;

  /**
   * Rewrite types:
   *
   * - No type or function types - emit unchecked
   * - Array and map types - emit `array` type hint
   * - Union types - Apply for all components
   * - Otherwise, simply use type literal as-is
   */
  protected function literal($type) {
    if (null === $type || $type instanceof IsFunction) {
      return '';
    } else if ($type instanceof IsArray || $type instanceof IsMap) {
      return 'array';
    } else if ($type instanceof IsUnion) {
      $literal= '';
      foreach ($type->components as $component) {
        $literal.= '|'.$this->literal($component);
      }
      return substr($literal, 1);
    } else {
      return $type->literal();
    }
  }

  protected function paramType($type) { return $this->literal($type); }

  protected function returnType($type) { return $this->literal($type); }

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