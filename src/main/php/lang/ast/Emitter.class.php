<?php namespace lang\ast;

use io\streams\OutputStream;
use lang\ast\{Node, Error, Errors};
use lang\{IllegalArgumentException, IllegalStateException, ClassLoader, XPClass};

abstract class Emitter {
  private $transformations= [];

  /**
   * Selects the correct emitter for a given runtime
   *
   * @param  string $runtime E.g. "php:".PHP_VERSION
   * @param  string[]|lang.XPClass[] $emitters Optional
   * @return lang.XPClass
   * @throws lang.IllegalArgumentException
   */
  public static function forRuntime($runtime, $emitters= []) {
    sscanf($runtime, '%[^.:]%*[.:]%d.%d', $engine, $major, $minor);
    $cl= ClassLoader::getDefault();

    $engine= strtoupper($engine);
    do {
      $impl= "lang.ast.emit.{$engine}{$major}{$minor}";
      if ($cl->providesClass($impl)) {
        if (empty($emitters)) return $cl->loadClass($impl);

        // Extend loaded class, including all given emitters
        $extended= ['kind' => 'class', 'extends' => [$cl->loadClass($impl)], 'implements' => [], 'use' => []];
        foreach ($emitters as $class) {
          if ($class instanceof XPClass) {
            $impl.= '⋈'.strtr($class->getName(), ['.' => '·']);
            $extended['use'][]= $class;
          } else {
            $impl.= '⋈'.strtr($class, ['.' => '·', '\\' => '·']);
            $extended['use'][]= XPClass::forName($class);
          }
        }
        return ClassLoader::defineType($impl, $extended, '{}');
      }
    } while ($minor-- > 0);

    throw new IllegalArgumentException('XP Compiler does not support '.$runtime.' yet');
  }

  /**
   * Raises an exception
   *
   * @param  string $error
   * @param  ?Throwable $cause
   * @return never
   */
  public function raise($error, $cause= null) {
    throw new IllegalStateException($error, $cause);
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
   * Standalone operators
   *
   * @param  lang.ast.Result $result
   * @param  lang.ast.Token $operator
   * @return void
   */
  protected function emitOperator($result, $operator) {
    throw new IllegalStateException('Unexpected operator '.$operator->value.' at line '.$operator->line);
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

    // Check for transformations
    if (isset($this->transformations[$node->kind])) {
      foreach ($this->transformations[$node->kind] as $transformation) {
        $r= $transformation($result->codegen, $node);
        if ($r instanceof Node) {
          if ($r->kind === $node->kind) continue;
          $this->{'emit'.$r->kind}($result, $r);
          return;
        } else if ($r) {
          foreach ($r as $s => $n) {
            $this->{'emit'.$n->kind}($result, $n);
            null === $s || $result->out->write(';');
          }
          return;
        }
      }
      // Fall through, use default
    }

    $this->{'emit'.$node->kind}($result, $node);
  }

  /**
   * Creates result
   *
   * @param  io.streams.OutputStream $target
   * @return lang.ast.Result
   */
  protected abstract function result($target);

  /**
   * Emitter entry point, takes nodes and emits them to the given target.
   *
   * @param  iterable $nodes
   * @param  io.streams.OutputStream $target
   * @param  ?string $source
   * @return io.streams.OutputStream
   * @throws lang.ast.Errors
   */
  public function write($nodes, OutputStream $target, $source= null) {
    $result= $this->result($target)->from($source);
    try {
      $this->emitAll($result, $nodes);
      return $target;
    } catch (Error $e) {
      throw new Errors([$e], $source);
    } finally {
      $result->close();
    }
  }
}