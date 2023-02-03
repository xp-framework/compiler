<?php namespace lang\ast\unittest\cli;

use io\streams\FileInputStream;
use io\{File, Folder};
use lang\Environment;
use test\{After, Assert, Before, Test, Values};
use xp\compiler\FromFilesIn;

class FromFilesInTest {
  private $folder;

  /** @return iterable */
  private function files() {
    yield [[]];

    $a= new File($this->folder, 'A.php');
    $a->touch();
    yield [['A.php' => FileInputStream::class]];

    $b= new File($this->folder, 'B.php');
    $b->touch();
    yield [['A.php' => FileInputStream::class, 'B.php' => FileInputStream::class]];

    $child= new Folder($this->folder, 'c');
    $child->create();
    $c= new File($child, 'C.php');
    $c->touch();
    yield [['A.php' => FileInputStream::class, 'B.php' => FileInputStream::class, 'c/C.php' => FileInputStream::class]];
  }

  #[Before]
  public function folder() {
    $this->folder= new Folder(Environment::tempDir(), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();
  }

  #[After]
  public function cleanup() {
    $this->folder->unlink();
  }

  #[Test]
  public function can_create() {
    new FromFilesIn($this->folder);
  }

  #[Test]
  public function can_create_from_string() {
    new FromFilesIn($this->folder->getURI());
  }

  #[Test, Values(from: 'files')]
  public function iteration($expected) {
    $results= [];
    foreach (new FromFilesIn($this->folder) as $path => $stream) {
      $results[$path->toString('/')]= get_class($stream);
    }

    Assert::equals($expected, $results);
  }
}