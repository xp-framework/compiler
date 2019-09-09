<?php namespace lang\ast\emit;

/**
 * Rewrites lambda expressions (or "arrow functions") to regular closures.
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2
 */
trait RewriteLambdaExpressions {

  protected function emitLambda($result, $lambda) {
    $capture= [];
    foreach ($this->search($lambda, 'variable') as $var) {
      if (isset($result->locals[$var->name])) {
        $capture[$var->name]= true;
      }
    }
    unset($capture['this']);

    $result->stack[]= $result->locals;
    $result->locals= [];

    $result->out->write('function');
    $this->emitSignature($result, $lambda->signature);
    foreach ($lambda->signature->parameters as $param) {
      unset($capture[$param->name]);
    }

    if ($capture) {
      $result->out->write(' use($'.implode(', $', array_keys($capture)).')');
      foreach ($capture as $name => $_) {
        $result->locals[$name]= true;
      }
    }

    if (is_array($lambda->body)) {
      $result->out->write('{');
      $this->emitAll($result, $lambda->body);
      $result->out->write('}');
    } else {
      $result->out->write('{ return ');
      $this->emitOne($result, $lambda->body);
      $result->out->write('; }');
    }

    $result->locals= array_pop($result->stack);
  }
}