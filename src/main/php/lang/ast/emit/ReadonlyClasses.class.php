<?php namespace lang\ast\emit;

use lang\ast\Code;

/**
 * Implements readonly properties by removing the `readonly` modifier from
 * the class and inheriting it to all properties (and promoted constructor
 * arguments).
 *
 * @see  https://wiki.php.net/rfc/readonly_classes
 */
trait ReadonlyClasses {

  protected function emitClass($result, $class) {
    if (false !== ($p= array_search('readonly', $class->modifiers))) {
      unset($class->modifiers[$p]);

      // Inherit
      foreach ($class->body as $member) {
        if ($member->is('property')) {
          $member->modifiers[]= 'readonly';
        } else if ($member->is('method')) {
          foreach ($member->signature->parameters as $param) {
            $param->promote && $param->promote.= ' readonly';
          }
        }
      }

      // Prevent dynamic members
      $throw= new Code('throw new \\Error("Cannot create dynamic property ".__CLASS__."::".$name);');
      $result->locals= [[], [], [null => [$throw, $throw]]];
    }

    return parent::emitClass($result, $class);
  }
}