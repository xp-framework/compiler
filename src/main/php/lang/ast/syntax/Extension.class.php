<?php namespace lang\ast\syntax;

interface Extension {

  /**
   * Register this syntax extension with the language and emitter
   *
   * @param  lang.ast.Language $language
   * @param  lang.ast.Emitter $emitter
   * @return void
   */
  public function setup($language, $emitter);
}