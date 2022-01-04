<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\php\XpMeta;

/**
 * Annotations via XP Meta information
 *
 * @see  https://github.com/xp-framework/rfc/issues/16
 * @see  https://github.com/xp-framework/rfc/issues/218
 */
class AnnotationsTest extends AnnotationSupport {

  /** @return string[] */
  protected function emitters() { return [XpMeta::class]; }

}