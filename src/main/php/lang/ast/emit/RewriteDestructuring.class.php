<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  BinaryExpression,
  InstanceExpression,
  Literal,
  OffsetExpression,
  ScopeExpression,
  UnaryExpression,
  Variable
};

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

    $temp= new Variable(substr($t, 1));
    $this->emitOne($result, $assignment->expression);
    $result->out->write(')?[');
    foreach ($assignment->variable->values as $i => $pair) {
      if (null === $pair[1]) {
        $result->out->write('null,');
        continue;
      }

      // Assign by reference
      $value= new OffsetExpression($temp, $pair[0] ?? new Literal((string)$i));
      if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->expression, '=&', $value));
        $default= null;
      } else if ($pair[1] instanceof BinaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->left, '=&', $value));
        $default= $pair[1];
      } else {
        $this->emitAssignment($result, new Assignment($pair[1], '=', $value));
        $default= null;
      }

      // Null-coalesce
      if ($default) {
        $result->out->write($default->operator);
        $this->emitOne($result, $default->right);
      }
      $result->out->write(',');
    }

    $null= new Literal('null');
    $result->out->write(']:([');
    foreach ($assignment->variable->values as $pair) {
      if (null === $pair[1]) {
        continue;
      } else if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->expression, '=', $null));
      } else if ($pair[1] instanceof BinaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->left, '=', $null));
      } else if ($pair[1]) {
        $this->emitAssignment($result, new Assignment($pair[1], '=', $null));
      }
      $result->out->write(',');
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