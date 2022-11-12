<?php namespace lang\ast\emit;

use lang\ast\nodes\{UnaryExpression, BinaryExpression, Variable, Literal, InstanceExpression, ScopeExpression};

/**
 * Rewrites list reference assignments and null-coalesce for PHP <= 7.3
 *
 * @see  https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see  https://wiki.php.net/rfc/list_reference_assignment
 */
trait RewriteAssignments {
  use RewriteDestructuring { emitAssignment as private; }

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
      foreach ($assignment->variable->values as $pair) {
        if (
          $pair[1] instanceof UnaryExpression ||
          $pair[1] instanceof BinaryExpression
        ) return $this->rewriteDestructuring($result, $assignment);
      }
    }

    return parent::emitAssignment($result, $assignment);
  }
}