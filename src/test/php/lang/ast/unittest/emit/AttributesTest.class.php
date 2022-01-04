<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\php\XpMeta;

/**
 * Annotations via PHP 8 attributes
 *
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax_change
 */
class AttributesTest extends AnnotationSupport {

  /** @return string[] */
  protected function emitters() { return []; }

}