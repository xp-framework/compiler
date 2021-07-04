<?php namespace lang\ast\emit;

/**
 * Rewrites callable expressions to regular closures
 *
 * @see  https://wiki.php.net/rfc/first_class_callable_syntax
 */
trait CallablesAsClosures {

  protected function emitCallable($result, $callable) {

    // Use variables in the following cases:
    //
    // $closure(...);                => use ($closure)
    // $obj->method(...);            => use ($obj)
    // $obj->$method(...);           => use ($obj, $method)
    // ($obj->property)(...);        => use ($obj)
    // $class::$method(...);         => use ($class, $method)
    // [$obj, 'method'](...);        => use ($obj)
    // [Foo::class, $method](...);   => use ($method)
    $use= [];
    foreach ($result->codegen->search($callable, 'variable') as $var) {
      $use[$var->name]= true;
    }
    unset($use['this']);

    // Create closure
    $t= $result->temp();
    $result->out->write('function(...'.$t.')');
    $use && $result->out->write('use($'.implode(', $', array_keys($use)).')');
    $result->out->write('{ return ');
    $this->emitOne($result, $callable->expression);
    $result->out->write('(... '.$t.'); }');
  }
}