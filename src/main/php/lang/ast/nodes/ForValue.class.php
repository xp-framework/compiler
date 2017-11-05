<?php namespace lang\ast\nodes;

class ForValue extends Value {
  public $initialization, $condition, $loop, $body;

  public function __construct($initialization, $condition, $loop, $body) {
    $this->initialization= $initialization;
    $this->condition= $condition;
    $this->loop= $loop;
    $this->body= $body;
  }
}