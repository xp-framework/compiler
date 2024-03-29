<?php namespace xp\compiler;

use io\{File, Folder};

class ToFolder extends ToFileSystem {
  private $folder;

  /** @param string|io.Folder $folder */
  public function __construct($folder) {
    $this->folder= $folder instanceof Folder ? $folder : new Folder($folder);
  }

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name) {
    if ('-' === $name) {
      $f= new File($this->folder, 'out'.$this->extension);
    } else {
      $f= new File($this->folder, str_replace('.php', $this->extension, $name));
    }
    $this->ensure($f->path);
    return $f->out();
  }
}