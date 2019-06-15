<?php namespace lang\ast\emit;

/**
 * Rewrites lambda expressions (or "arrow functions") to regular closures.
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2
 */
trait RewriteLambdaExpressions {

  protected function emitLambda($lambda) {
    $capture= [];
    foreach ($this->search($lambda->body, 'variable') as $var) {
      if (isset($this->locals[$var->value])) {
        $capture[$var->value]= true;
      }
    }
    unset($capture['this']);

    $this->stack[]= $this->locals;
    $this->locals= [];

    $this->out->write('function');
    $this->emitSignature($lambda->signature);
    foreach ($lambda->signature->parameters as $param) {
      unset($capture[$param->name]);
    }

    if ($capture) {
      $this->out->write(' use($'.implode(', $', array_keys($capture)).')');
      foreach ($capture as $name => $_) {
        $this->locals[$name]= true;
      }
    }

    if (is_array($lambda->body)) {
      $this->out->write('{');
      $this->emit($lambda->body);
      $this->out->write('}');
    } else {
      $this->out->write('{ return ');
      $this->emit($lambda->body);
      $this->out->write('; }');
    }

    $this->locals= array_pop($this->stack);
  }
}