<?php namespace lang\ast\emit;

/**
 * Rewrites static variable initializations to non-constant expressions.
 *
 * @see  https://github.com/xp-framework/compiler/issues/162
 * @see  https://wiki.php.net/rfc/arbitrary_static_variable_initializers
 */
trait RewriteStaticVariableInitializations {

  protected function emitStatic($result, $static) {
    foreach ($static->initializations as $variable => $initial) {
      $result->out->write("static \${$variable}");
      if ($initial) {
        if ($this->isConstant($result, $initial)) {
          $result->out->write('=');
          $this->emitOne($result, $initial);
        } else {
          $result->out->write("= null; null === \${$variable} && \${$variable}=");
          $this->emitOne($result, $initial);
        }
      }
      $result->out->write(';');
    }
  }
}