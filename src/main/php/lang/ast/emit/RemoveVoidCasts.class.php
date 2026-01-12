<?php namespace lang\ast\emit;

/**
 * Removes (void) casts for any PHP version < 8.5
 *
 * @see  https://wiki.php.net/rfc/marking_return_value_as_important
 */
trait RemoveVoidCasts {

  protected function emitCast($result, $cast) {
    if ('void' === $cast->type->name()) {
      $this->emitOne($result, $cast->expression);
    } else {
      parent::emitCast($result, $cast);
    }
  }
}