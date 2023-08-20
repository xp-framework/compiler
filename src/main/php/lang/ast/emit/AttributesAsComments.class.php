<?php namespace lang\ast\emit;

/**
 * Rewrites PHP 8 attributes as comments for PHP 7, ensuring they are
 * always on a line by themselves *and* never span more than one line.
 *
 * This will break line numbers if annotations are on the same line as
 * the element they belong to and make it harder to debug but guarantees
 * the emitted code is syntactically correct!
 *
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax_change
 */
trait AttributesAsComments {

  protected function emitAnnotation($result, $annotation) {
    $result->out->write("\\{$annotation->name}");
    if (empty($annotation->arguments)) return;

    // Check whether arguments are constant, enclose in `eval` array
    // otherwise. This is not strictly necessary but will ensure
    // forward compatibility with PHP 8
    foreach ($annotation->arguments as $argument) {
      if ($this->isConstant($result, $argument)) continue;

      $escaping= new Escaping($result->out, ["'" => "\\'", '\\' => '\\\\']);
      $result->out->write('(eval: [');
      foreach ($annotation->arguments as $name => $argument) {
        is_string($name) && $result->out->write("'{$name}'=>");

        $result->out->write("'");
        $result->out= $escaping;
        $this->emitOne($result, $argument);
        $result->out= $escaping->original();
        $result->out->write("',");
      }
      $result->out->write('])');
      return;
    }

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
    $line= $annotations->line;
    $result->out->write('#[');

    $result->out= new Escaping($result->out, ["\n" => " "]);
    foreach ($annotations->named as $annotation) {
      $this->emitOne($result, $annotation);
      $result->out->write(',');
    }
    $result->out= $result->out->original();

    $result->out->write("]\n");
    $result->line= $line + 1;
  }
}