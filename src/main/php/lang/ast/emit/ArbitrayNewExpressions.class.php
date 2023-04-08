<?php namespace lang\ast\emit;

use lang\ast\nodes\Variable;
use lang\ast\types\IsExpression;

/**
 * Rewrites `new` with arbitrary expressions
 *
 * @see  https://wiki.php.net/rfc/variable_syntax_tweaks
 */
trait ArbitrayNewExpressions {
 
  protected function emitNew($result, $new) {
    if (!($new->type instanceof IsExpression)) return parent::emitNew($result, $new);

    // Emit supported `new $var`, rewrite unsupported `new ($expr)`
    if ($new->type->expression instanceof Variable && $new->type->expression->const) {
      $result->out->write('new $'.$new->type->expression->pointer.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(')');
    } else {
      $t= $result->temp();
      $result->out->write('('.$t.'= ');
      $this->emitOne($result, $new->type->expression);
      $result->out->write(') ? new '.$t.'(');
      $this->emitArguments($result, $new->arguments);
      $result->out->write(') : null');
    }
  }
}