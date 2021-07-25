<?php namespace xp\compiler;

use Traversable;

/** Various inputs */
class FromInputs extends Input {
  private $inputs;

  /**
   * Creates a new instance
   *
   * @param string[] $in
   */
  public function __construct($in) {
    $this->in= $in;
  }

  /** @return iterable */
  public function getIterator(): Traversable {
    foreach ($this->in as $in) {
      foreach (parent::newInstance($in) as $path => $stream) {
        yield $path => $stream;
      }
    }
  }
}