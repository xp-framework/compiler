<?php namespace lang\ast\emit;

use lang\ast\nodes\{
  Assignment,
  InstanceExpression,
  Literal,
  OffsetExpression,
  ScopeExpression,
  UnaryExpression,
  Variable
};

/**
 * Rewrites list reference assignments and null-coalesce for PHP <= 7.3
 *
 * @see  https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see  https://wiki.php.net/rfc/list_reference_assignment
 */
trait RewriteAssignments {

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
      $value= new OffsetExpression($temp, $pair[0] ?? new Literal($i));
      if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->expression, '=&', $value));
      } else {
        $this->emitAssignment($result, new Assignment($pair[1], '=', $value));
      }
      $result->out->write(',');
    }

    $null= new Literal('null');
    $result->out->write(']:([');
    foreach ($assignment->variable->values as $pair) {
      if ($pair[1] instanceof UnaryExpression) {
        $this->emitAssignment($result, new Assignment($pair[1]->expression, '=', $null));
      } else if ($pair[1]) {
        $this->emitAssignment($result, new Assignment($pair[1], '=', $null));
      }
      $result->out->write(',');
    }
    $result->out->write(']?'.$t.':null)');
  }

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {

      // Rewrite null-coalesce operator
      $this->emitAssign($result, $assignment->variable);
      $result->out->write('??');
      $this->emitOne($result, $assignment->variable);
      $result->out->write('=');
      $this->emitOne($result, $assignment->expression);
      return;
    } else if ('array' === $assignment->variable->kind) {

      // Rewrite destructuring unless assignment consists only of variables
      $r= false;
      foreach ($assignment->variable->values as $pair) {
        if (null === $pair[1] || $pair[1] instanceof Variable) continue;
        $r= true;
        break;
      }
      if ($r) return $this->rewriteDestructuring($result, $assignment);
    }

    return parent::emitAssignment($result, $assignment);
  }
}