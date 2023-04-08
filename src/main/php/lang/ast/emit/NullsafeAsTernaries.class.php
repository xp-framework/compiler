<?php namespace lang\ast\emit;

/**
 * Rewrites null safe operator to ternaries
 *
 * @see  https://wiki.php.net/rfc/nullsafe_operator
 */
trait NullsafeAsTernaries {

  protected function emitNullsafeInstance($result, $instance) {
    $t= $result->temp();
    $result->out->write('null===('.$t.'=');
    $this->emitOne($result, $instance->expression);
    $result->out->write(')?null:'.$t.'->');
    $this->emitOne($result, $instance->member);
  }
}