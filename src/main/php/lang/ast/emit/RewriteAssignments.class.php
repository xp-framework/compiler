<?php namespace lang\ast\emit;

use lang\ast\nodes\{UnaryExpression, Variable, Literal, InstanceExpression, ScopeExpression};

/**
 * Rewrites list reference assignments and null-coalesce for PHP <= 7.3
 *
 * @see  https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see  https://wiki.php.net/rfc/list_reference_assignment
 */
trait RewriteAssignments {

  protected function emitAssignment($result, $assignment) {
    if ('??=' === $assignment->operator) {
      $this->emitAssign($result, $assignment->variable);
      $result->out->write('??');
      $this->emitOne($result, $assignment->variable);
      $result->out->write('=');
      $this->emitOne($result, $assignment->expression);
    } else if ('array' === $assignment->variable->kind) {

      // Check whether the list assignment consists only of variables
      $supported= true;
      foreach ($assignment->variable->values as $pair) {
        if ($pair[1] instanceof Variable) continue;
        $supported= false;
        break;
      }
      if ($supported) return parent::emitAssignment($result, $assignment);

      $t= $result->temp();
      $result->out->write('null===('.$t.'=');

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
      $result->out->write(')?null:[');
      foreach ($assignment->variable->values as $i => $pair) {

        // Assign by reference
        if ($pair[1] instanceof UnaryExpression) {
          $this->emitAssign($result, $pair[1]->expression);
          $result->out->write('='.$pair[1]->operator.$t.'['.$i.'],');  
        } else {
          $this->emitAssign($result, $pair[1]);
          $result->out->write('='.$t.'['.$i.'],');  
        }
      }
      $result->out->write(']');
    } else {
      return parent::emitAssignment($result, $assignment);
    }
  }
}