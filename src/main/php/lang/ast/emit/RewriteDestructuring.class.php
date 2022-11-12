<?php namespace lang\ast\emit;

use lang\ast\nodes\{UnaryExpression, BinaryExpression, Variable, Literal, InstanceExpression, ScopeExpression};

trait RewriteDestructuring {

  protected function rewriteDestructuring($result, $assignment) {
    $t= $result->temp();
    $result->out->write('is_array('.$t.'=');

    // Create reference to right-hand if possible
    $r= $assignment->expression;
    if (
      ($r instanceof Variable) ||
      ($r instanceof InstanceExpression && $r->member instanceof Literal) ||
      ($r instanceof ScopeExpression && $r->member instanceof Variable)
    ) {
      $result->out->write('&');
    }

    $this->emitOne($result, $assignment->expression);
    $result->out->write(')?[');
    foreach ($assignment->variable->values as $i => $pair) {
      if (null === $pair[1]) {
        $result->out->write('null,');
        continue;
      }

      // Assign by reference
      if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssign($result, $pair[1]->expression);
        $result->out->write('='.$pair[1]->operator.$t.'[');
        $default= null;
      } else if ($pair[1] instanceof BinaryExpression) {
        $this->emitAssign($result, $pair[1]->left);
        $result->out->write('='.$t.'[');
        $default= $pair[1];
      } else {
        $this->emitAssign($result, $pair[1]);
        $result->out->write('='.$t.'[');
        $default= null;
      }

      if ($pair[0]) {
        $this->emitOne($result, $pair[0]);
        $result->out->write(']');
      } else {
        $result->out->write($i.']');
      }

      // Null-coalesce
      if ($default) {
        $result->out->write($default->operator);
        $this->emitOne($result, $default->right);
      }
      $result->out->write(',');
    }
    $result->out->write(']:([');
    foreach ($assignment->variable->values as $pair) {
      if (null === $pair[1]) {
        continue;
      } else if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssign($result, $pair[1]->expression);
      } else if ($pair[1] instanceof BinaryExpression) {
        $this->emitAssign($result, $pair[1]->left);
      } else if ($pair[1]) {
        $this->emitAssign($result, $pair[1]);
      }
      $result->out->write('=null,');
    }
    $result->out->write(']?'.$t.':null)');
  }

  protected function emitAssignment($result, $assignment) {
    if ('array' === $assignment->variable->kind) {
      foreach ($assignment->variable->values as $pair) {
        if ($pair[1] instanceof BinaryExpression) return $this->rewriteDestructuring($result, $assignment);
      }
    }

    parent::emitAssignment($result, $assignment);
  }
}