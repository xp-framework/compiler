<?php namespace xp\compiler;

use lang\ast\{Language, Emitter};
use lang\reflect\Package;
use util\cmd\Console;

/** @codeCoverageIgnore */
class Usage {
  const RUNTIME = 'php';

  /** @return int */
  public static function main(array $args) {
    Console::$err->writeLine('Usage: xp compile <in> [<out>]');

    $impl= new class() {
      public $byLoader= [];

      public function add($t, $active= false) {
        $this->byLoader[$t->getClassLoader()->toString()][$t->getName()]= $active;
      }
    };

    $emitter= Emitter::forRuntime(self::RUNTIME.':'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION);
    foreach (Package::forName('lang.ast.emit')->getClasses() as $class) {
      if ($class->isSubclassOf(Emitter::class) && !(MODIFIER_ABSTRACT & $class->getModifiers())) {
        $impl->add($class, $class->equals($emitter));
      }
    }

    $language= Language::named(self::RUNTIME);
    foreach (Package::forName('lang.ast.syntax')->getClasses() as $class) {
      if ($class->isSubclassOf(Language::class) && !(MODIFIER_ABSTRACT & $class->getModifiers())) {
        $impl->add($class, $class->isInstance($language));
      }
    }

    foreach ($language->extensions() as $extension) {
      $impl->add(typeof($extension), 'true');
    }

    // Show implementations sorted by class loader
    foreach ($impl->byLoader as $loader => $list) {
      Console::$err->writeLine();
      Console::$err->writeLine("\033[33m@", $loader, "\033[0m");
      foreach ($list as $impl => $active) {
        Console::$err->writeLine($impl, $active ? " [\033[36m*\033[0m]" : '');
      }
    }
    return 2;
  }
}