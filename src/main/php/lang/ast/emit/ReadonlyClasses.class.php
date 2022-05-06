<?php namespace lang\ast\emit;

/**
 * Implements readonly properties by removing the `readonly` modifier from
 * the class and inheriting it to all properties.
 *
 * @see  https://wiki.php.net/rfc/readonly_classes
 */
trait ReadonlyClasses {

  protected function emitClass($result, $class) {
    if (false !== ($p= array_search('readonly', $class->modifiers))) {
      unset($class->modifiers[$p]);
      foreach ($class->body as $member) {
        if ($member->is('property')) $member->modifiers[]= 'readonly';
      }
    }

    return parent::emitClass($result, $class);
  }
}