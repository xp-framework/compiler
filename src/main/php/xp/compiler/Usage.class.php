<?php namespace xp\compiler;

use lang\Reflection;
use lang\ast\{Language, Emitter};
use lang\reflection\Package;
use util\cmd\Console;

/** @codeCoverageIgnore */
class Usage {
  const RUNTIME= 'php';

  /** @return int */
  public static function main(array $args) {
    Console::$err->writeLine('Usage: xp compile <in> [<out>]');

    $impl= new class() {
      public $byLoader= [];

      public function add($t, $active= false) {
        $this->byLoader[$t->classLoader()->toString()][$t->name()]= $active;
      }
    };

    $emitter= Emitter::forRuntime(self::RUNTIME.':'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION);
    foreach ((new Package('lang.ast.emit'))->types() as $type) {
      if ($type->is(Emitter::class) && !$type->modifiers()->isAbstract()) {
        $impl->add($type, $type->class()->equals($emitter));
      }
    }

    $language= Language::named(strtoupper(self::RUNTIME));
    foreach ((new Package('lang.ast.syntax'))->types() as $type) {
      if ($type->is(Language::class) && !$type->modifiers()->isAbstract()) {
        $impl->add($type, $type->isInstance($language));
      }
    }

    foreach ($language->extensions() as $extension) {
      $impl->add(Reflection::type($extension), true);
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