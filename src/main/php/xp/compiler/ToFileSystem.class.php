<?php namespace xp\compiler;

use io\Folder;

abstract class ToFileSystem implements Output {

  /**
   * Ensures a given path exists, creating it if necessary
   *
   * @param  string $path
   * @return void
   */
  protected function ensure($path) {
    $f= new Folder($path);
    $f->exists() || $f->create();
  }
}