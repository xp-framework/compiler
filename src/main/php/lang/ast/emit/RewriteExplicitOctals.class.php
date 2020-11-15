<?php namespace lang\ast\emit;

/**
 * Rewrites explicit octal notations: `0o16` => `016`.
 *
 * @see  https://wiki.php.net/rfc/explicit_octal_notation
 */
trait RewriteExplicitOctals {

  protected function emitLiteral($result, $literal) {
    if (0 === strncasecmp($literal->expression, '0o', 2)) {
      $result->out->write('0'.substr($literal->expression, 2));
    } else {
      $result->out->write($literal->expression);
    }
  }
}