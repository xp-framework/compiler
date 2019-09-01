<?php namespace lang\ast\emit;

/**
 * Rewrites multiple catch exception types
 *
 * @see  https://wiki.php.net/rfc/multiple-catch
 */
trait RewriteMultiCatch {
 
  protected function emitCatch($result, $catch) {
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable $'.$catch->variable.') {');
    } else {
      $last= array_pop($catch->types);
      $label= sprintf('c%u', crc32($last));
      foreach ($catch->types as $type) {
        $result->out->write('catch('.$type.' $'.$catch->variable.') { goto '.$label.'; }');
      }
      $result->out->write('catch('.$last.' $'.$catch->variable.') { '.$label.':');
    }

    $this->emit($result, $catch->body);
    $result->out->write('}');
  }
}