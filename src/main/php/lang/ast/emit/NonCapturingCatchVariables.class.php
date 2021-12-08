<?php namespace lang\ast\emit;

/**
 * Uses temporary variables for non-capturing catches
 *
 * @see  https://wiki.php.net/rfc/non-capturing_catches
 */
trait NonCapturingCatchVariables {

  protected function emitCatch($result, $catch) {
    $capture= $catch->variable ? '$'.$catch->variable : $result->temp();
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable '.$capture.') {');
    } else {
      $result->out->write('catch('.implode('|', $catch->types).' '.$capture.') {');
    }
    $this->emitAll($result, $catch->body);
    $result->out->write('}');
  }
}