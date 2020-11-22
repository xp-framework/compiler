<?php namespace lang\ast\emit;

/**
 * Rewrites multiple catch exception types
 *
 * @see  https://wiki.php.net/rfc/multiple-catch
 */
trait RewriteMultiCatch {
 
  protected function emitCatch($result, $catch) {
    $capture= $catch->variable ? '$'.$catch->variable : $result->temp();
    if (empty($catch->types)) {
      $result->out->write('catch(\\Throwable '.$capture.') {');
    } else {
      $last= array_pop($catch->types);
      $label= sprintf('c%u', crc32($last));
      foreach ($catch->types as $type) {
        $result->out->write('catch('.$type.' '.$capture.') { goto '.$label.'; }');
      }
      $result->out->write('catch('.$last.' '.$capture.') { '.$label.':');
    }

    $this->emitAll($result, $catch->body);
    $result->out->write('}');
  }
}