<?php namespace lang\ast\unittest\cli;

use io\streams\FileInputStream;
use io\{File, Folder};
use lang\Environment;
use unittest\{Assert, After, Before, Test, Values};
use xp\compiler\FromFilesIn;

class FromFilesInTest {
  private $folder;

  /** @return iterable */
  private function files() {
    yield [[]];

    $a= new File($this->folder, 'A.php');
    $a->touch();
    yield [[$a->getFileName() => $a->in()]];

    $b= new File($this->folder, 'B.php');
    $b->touch();
    yield [[$a->getFileName() => $a->in(), $b->getFileName() => $b->in()]];
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

  #[Test, Values('files')]
  public function iteration($expected) {
    $results= [];
    foreach (new FromFilesIn($this->folder) as $path => $stream) {
      $results[(string)$path]= $stream;
    }

    Assert::equals($expected, $results);
  }
}