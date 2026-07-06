<?php namespace lang\ast\emit;

class InType {
  public $meta= [];
  public $init= [];
  public $statics= [];
  public $virtual= [];

  public function __construct(public $type) { }
}