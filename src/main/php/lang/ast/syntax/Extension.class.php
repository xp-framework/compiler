<?php namespace lang\ast\syntax;

interface Extension {

  public function setup($language, $emitter);
}