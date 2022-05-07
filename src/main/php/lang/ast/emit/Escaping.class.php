<?php namespace lang\ast\emit;

use io\streams\OutputStream;

class Escaping implements OutputStream {
  private $target, $replacements;

  public function __construct(OutputStream $target, array $replacements) {
    $this->target= $target;
    $this->replacements= $replacements;
  }

  public function write($bytes) {
    $this->target->write(strtr($bytes, $this->replacements));
  }

  public function flush() {
    $this->target->flush();
  }

  public function close() {
    $this->target->close();
  }

  public function original() {
    return $this->target;
  }
}