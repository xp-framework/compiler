<?php namespace lang\ast\emit;

use lang\ast\Node;

/**
 * Rewrites `new` with arbitrary expressions
 *
 * @see  https://wiki.php.net/rfc/variable_syntax_tweaks
 */
trait ArbitrayNewExpressions {
 
  protected function emitNew($result, $new) {
    if ($new->type instanceof Node) {
      $t= $result->temp();
      $result->out->write('('.$t.'= ');
      $this->emitOne($result, $new->type);
      $result->out->write(') ? new '.$t.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(') : null');
    } else {
      $result->out->write('new '.$new->type.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(')');
    }
  }
}