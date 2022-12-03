<?php namespace lang\ast\emit;

class InType {
  const STATICS= 0;
  const INSTANCE= 1;

  public $type;
  public $meta= [];
  public $init= [[], []];
  public $virtual= [];

  public function __construct($type) {
    $this->type= $type;
  }
}