<?php namespace lang\ast\emit;

class InType {
  public $type;
  public $meta= [];
  public $init= [];
  public $statics= [];
  public $virtual= [];
  public $defaultImplementations= [];

  public function __construct($type) {
    $this->type= $type;
  }
}