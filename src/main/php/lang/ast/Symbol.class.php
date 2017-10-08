<?php namespace lang\ast;

class Symbol implements \lang\Value {
  public $id, $lbp= 0, $std= null, $nud, $led;

  public function error($message) {
    throw new Error($message.' '.$this->id);
  }

  public function hashCode() {
    return $this->id;
  }

  public function toString() {
    return nameof($this).'("'.$this->id.'", lbp= '.$this->lbp.')';
  }

  public function compareTo($that) {
    return $that instanceof self ? strcmp($this->id, $that->id) : 1;
  }
}