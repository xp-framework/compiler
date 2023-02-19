<?php namespace lang\ast\emit\php;

trait WithoutPropertyTypes {

  /**
   * Returns type for use in properties, or NULL to use no type.
   *
   * @param  ?lang.ast.Type $type
   * @return ?string
   */
  protected function propertyType($type) { return null; }
}