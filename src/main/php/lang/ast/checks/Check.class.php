<?php namespace lang\ast\checks;

use lang\ast\{Error, Errors};

abstract class Check {

  /** @return string */
  public abstract function nodeKind();

  /**
   * Checks node and returns errors
   *
   * @param  lang.ast.CodeGen $codegen
   * @param  lang.ast.Node $node
   * @return iterable
   */
  public abstract function check($codegen, $node);

  /**
   * Attach this check to a given emitter.
   *
   * @param  lang.ast.Emitter $emit
   * @return void
   */
  public function attachTo($emit) {
    $emit->transform($this->nodeKind(), function($codegen, $node) {
      $errors= [];
      foreach ($this->check($codegen, $node) as $error) {
        $errors[]= new Error($error, $codegen->source, $node->line);
      }
      if (empty($errors)) return null;

      throw new Errors($errors, $codegen->source);
    });
  }
}