<?php namespace lang\ast\emit;

/**
 * Omits argument names
 *
 * @see  https://wiki.php.net/rfc/named_params
 */
trait OmitArgumentNames {

  protected function emitArguments($result, $arguments) {
    $i= 0;
    foreach ($arguments as $argument) {
      if ($i++) $result->out->write(',');
      $this->emitOne($result, $argument);
    }
  }
}