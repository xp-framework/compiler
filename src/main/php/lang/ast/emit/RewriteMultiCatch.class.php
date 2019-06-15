<?php namespace lang\ast\emit;

/**
 * Rewrites multiple catch exception types
 *
 * @see  https://wiki.php.net/rfc/multiple-catch
 */
trait RewriteMultiCatch {
 
  protected function emitCatch($catch) {
    if (empty($catch->types)) {
      $this->out->write('catch(\\Throwable $'.$catch->variable.') {');
    } else {
      $last= array_pop($catch->types);
      $label= sprintf('c%u', crc32($last));
      foreach ($catch->types as $type) {
        $this->out->write('catch('.$type.' $'.$catch->variable.') { goto '.$label.'; }');
      }
      $this->out->write('catch('.$last.' $'.$catch->variable.') { '.$label.':');
    }

    $this->emit($catch->body);
    $this->out->write('}');
  }
}