<?php namespace lang\ast\unittest\emit;

trait AnnotationsOf {

  /**
   * Returns annotations present in the given type
   *
   * @param  lang.reflection.Annotated $annotated
   * @return [:var[]]
   */
  private function annotations($annotated) {
    $r= [];
    foreach ($annotated->annotations() as $name => $annotation) {
      $r[$name]= $annotation->arguments();
    }
    return $r;
  }
}