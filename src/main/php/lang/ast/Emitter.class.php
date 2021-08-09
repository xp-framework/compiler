<?php namespace lang\ast;

use lang\ast\Node;
use lang\reflect\Package;
use lang\{ClassLoader, IllegalArgumentException, IllegalStateException};

abstract class Emitter {
  private $transformations= [];

  /**
   * Selects the correct emitter for a given runtime
   *
   * @param  string $runtime E.g. "PHP.".PHP_VERSION
   * @param  lang.XPClass[] $add Traits to add
   * @param  lang.XPClass[] $remove Traits to remove
   * @return lang.XPClass
   * @throws lang.IllegalArgumentException
   */
  public static function forRuntime($runtime, $add= [], $remove= []) {
    sscanf($runtime, '%[^.].%d.%d', $engine, $major, $minor);
    $p= Package::forName('lang.ast.emit');

    do {
      $impl= $engine.$major.$minor;
      if ($p->providesClass($impl)) {
        $class= $p->loadClass($impl);
        if (empty($add) && empty($remove)) return $class;

        // Modify emitter class
        $use= [];
        foreach ($class->getTraits() as $trait) {
          $use[$trait->getName()]= $trait;
        }
        foreach ($remove as $trait) {
          unset($use[$trait->getName()]);
        }
        foreach ($add as $trait) {
          $use[$trait->getName()]= $trait;
        }

        return ClassLoader::defineType(
          sprintf('Emit%u', crc32(implode('&', array_keys($use)))),
          ['kind' => 'class', 'extends' => [$class->getParentClass()], 'implements' => [], 'use' => $use],
          []
        );
      }
    } while ($minor-- > 0);

    throw new IllegalArgumentException('XP Compiler does not support '.$runtime.' yet');
  }

  /**
   * Transforms nodes of a certain kind using the given function, which
   * may return either single node, which will be then emitted, or an
   * iterable producing nodes, which will then be emitted as statements.
   * Returns a handle to remove the transformation again
   *
   * @param  string $kind
   * @param  function(lang.ast.Node): lang.ast.Node|iterable $function
   * @return var
   */
  public function transform($kind, $function) {
    if (isset($this->transformations[$kind])) {
      $i= sizeof($this->transformations[$kind]);
      $this->transformations[$kind][]= $function;
    } else {
      $i= 0;
      $this->transformations[$kind]= [$function];
    }
    return ['kind' => $kind, 'id' => $i];
  }

  /**
   * Removes a transformation added with transform()
   *
   * @param  var $transformation
   * @return void
   */
  public function remove($transformation) {
    $kind= $transformation['kind'];
    array_splice($this->transformations[$kind], $transformation['id'], 1);
    if (empty($this->transformations[$kind])) unset($this->transformations[$kind]);
  }

  /**
   * Returns all transformations
   *
   * @return [:var[]]
   */
  public function transformations() {
    return $this->transformations;
  }

  /**
   * Catch-all, should `$node->kind` be empty in `"emit{$node->kind}"`.
   *
   * @return void
   */
  protected function emit() {
    throw new IllegalStateException('Called without node kind');
  }

  /**
   * Emit nodes seperated as statements
   *
   * @param  lang.ast.Result $result
   * @param  iterable $nodes
   * @return void
   */
  public function emitAll($result, $nodes) {
    foreach ($nodes as $node) {
      $this->emitOne($result, $node);
      $result->out->write(';');
    }
  }

  /**
   * Emit single nodes
   *
   * @param  lang.ast.Result $result
   * @param  lang.ast.Node $node
   * @return void
   */
  public function emitOne($result, $node) {
    if ($node->line > $result->line) {
      $result->out->write(str_repeat("\n", $node->line - $result->line));
      $result->line= $node->line;
    }

    // Check for transformations
    if (isset($this->transformations[$node->kind])) {
      foreach ($this->transformations[$node->kind] as $transformation) {
        $r= $transformation($result->codegen, $node);
        if ($r instanceof Node) {
          if ($r->kind === $node->kind) continue;
          $this->{"emit{$r->kind}"}($result, $r);
          return;
        } else if ($r) {
          foreach ($r as $n) {
            $this->{"emit{$n->kind}"}($result, $n);
            $result->out->write(';');
          }
          return;
        }
      }
      // Fall through, use default
    }
    $this->{'emit'.$node->kind}($result, $node);
  }
}