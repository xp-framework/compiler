<?php namespace lang\ast\unittest\cli;

use io\{File, Folder};
use lang\Environment;
use lang\FileSystemClassLoader;
use unittest\{After, Assert, Before, Test};
use xp\compiler\ToFile;

class ToFileTest {
  private $folder, $target;

  #[Before]
  public function folder() {
    $this->folder= new Folder(realpath(Environment::tempDir()), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();

    $this->target= new File($this->folder, 'Test.class.php');
  }

  #[After]
  public function cleanup() {
    $this->folder->unlink();
  }

  #[Test]
  public function can_create() {
    new ToFile($this->target);
  }

  #[Test]
  public function write_to_target_then_load_via_class_loader() {
    $class= '<?php class Test { }';

    $fixture= new ToFile($this->target);
    with ($fixture->target('Test.php'), function($out) use($class) {
      $out->write($class);
      $out->flush();
      $out->close();
    });
    $fixture->close();

    Assert::equals($class, (new FileSystemClassLoader($this->folder->getURI()))->loadClassBytes('Test'));
  }
}