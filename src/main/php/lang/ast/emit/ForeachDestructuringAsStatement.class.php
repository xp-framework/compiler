<?php namespace lang\ast\emit;

use lang\ast\nodes\{Assignment, Variable};

/**
 * Rewrite destructuring assignment as first statement in `foreach` loops.
 * 
 * This ensures compatibility with:
 *
 * - PHP 7.0, which neither supports keys nor references
 * - PHP 7.1 and PHP 7.2, which do not support references
 *
 * @see  https://wiki.php.net/rfc/list_keys
 * @see  https://wiki.php.net/rfc/list_reference_assignment
 */
trait ForeachDestructuringAsStatement {

  protected function emitForeach($result, $foreach) {
    $result->out->write('foreach (');
    $this->emitOne($result, $foreach->expression);
    $result->out->write(' as ');
    if ($foreach->key) {
      $this->emitOne($result, $foreach->key);
      $result->out->write(' => ');
    }

    if ('array' === $foreach->value->kind) {
      $t= $result->temp();
      $result->out->write('&'.$t.') {');
      $this->rewriteDestructuring($result, new Assignment($foreach->value, '=', new Variable(substr($t, 1))));
      $result->out->write(';');
      $this->emitAll($result, $foreach->body);
      $result->out->write('}');
    } else {
      $this->emitOne($result, $foreach->value);
      $result->out->write(') {');
      $this->emitAll($result, $foreach->body);
      $result->out->write('}');
    }
  }
}