<?php namespace xp\compiler;

use io\Folder;

/** Source files inside a given folder */
class FromFilesIn extends Input {
  private $folder, $base;

  /**
   * Creates a new instance
   *
   * @param string|io.Folder $folder
   * @param string|io.Folder $base
   */
  public function __construct($folder, $base) {
    $this->folder= $folder instanceof Folder ? $folder : new Folder($folder);
    $this->base= $base instanceof Folder ? $base : new Folder($base);
  }

  /**
   * Returns all ".php" files in a given folder, recursively
   *
   * @param  io.Folder $folder
   * @return iterable
   */
  private function filesIn($folder) {
    foreach ($folder->entries() as $name => $entry) {
      if ($entry->isFolder()) {
        foreach ($this->filesIn($entry->asFolder()) as $uri => $stream) {
          yield $uri => $stream;
        }
      } else if (0 === substr_compare($name, '.php', -4)) {
        yield $entry->relativeTo($this->base)->toString() => $entry->asFile()->in();
      }
    }
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->filesIn($this->folder) as $uri => $stream) {
      yield $uri => $stream;
    }
  }
}