<?php namespace lang\ast\emit;

/**
 * Rewrites PHP 8 attributes as comments for PHP 7, ensuring they are
 * always on a line by themselves. This might break line numbers and
 * make it harder to debug but guarantees the emitted code is syntactically
 * correct!
 *
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax_change
 */
trait AttributesAsComments {

  protected function emitAnnotation($result, $annotation) {
    $result->out->write('\\'.$annotation->name);
    if (empty($annotation->arguments)) return;

    // We can use named arguments here as PHP 8 attributes are parsed
    // by the XP reflection API when using PHP 7. However, we may not
    // emit trailing commas here!
    $result->out->write('(');
    $i= 0;
    foreach ($annotation->arguments as $name => $argument) {
      $i++ > 0 && $result->out->write(',');
      is_string($name) && $result->out->write("{$name}:");
      $this->emitOne($result, $argument);
    }
    $result->out->write(')');
  }

  protected function emitAnnotations($result, $annotations) {
    $result->out->write('#[');
    foreach ($annotations->named as $annotation) {
      $this->emitOne($result, $annotation);
      $result->out->write(',');
    }
    $result->out->write("]\n");
    $result->line++;
  }
}