<?php namespace xp\compiler;

use io\Folder;

/** Source files inside a given folder */
class FromFilesIn extends Input {
  private $folder;

  /**
   * Creates a new instance
   *
   * @param string|io.Folder $folder
   */
  public function __construct($folder) {
    $this->folder= $folder instanceof Folder ? $folder : new Folder($folder);
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
        foreach ($this->filesIn($entry->asFolder()) as $path => $stream) {
          yield $path => $stream;
        }
      } else if (0 === substr_compare($name, '.php', -4)) {
        yield $entry->relativeTo($this->folder) => $entry->asFile()->in();
      }
    }
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->filesIn($this->folder) as $path => $stream) {
      yield $path => $stream;
    }
  }
}