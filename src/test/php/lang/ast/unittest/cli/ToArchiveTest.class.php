<?php namespace lang\ast\unittest\cli;

use io\{File, Folder};
use lang\Environment;
use lang\archive\ArchiveClassLoader;
use test\{After, Assert, Before, Test};
use xp\compiler\ToArchive;

class ToArchiveTest {
  private $folder, $archive;

  #[Before]
  public function folder() {
    $this->folder= new Folder(realpath(Environment::tempDir()), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();

    $this->archive= new File($this->folder, 'dist.xar');
    $this->archive->touch();
  }

  #[After]
  public function cleanup() {
    $this->archive->isOpen() && $this->archive->close();
    $this->folder->unlink();
  }

  #[Test]
  public function can_create() {
    new ToArchive($this->archive);
  }

  #[Test]
  public function write_to_target_then_load_via_class_loader() {
    $class= '<?php class Test { }';

    $fixture= new ToArchive($this->archive);
    with ($fixture->target('Test.php'), function($out) use($class) {
      $out->write($class);
      $out->flush();
      $out->close();
    });
    $fixture->close();

    Assert::equals($class, (new ArchiveClassLoader($this->archive->getURI()))->loadClassBytes('Test'));
  }
}