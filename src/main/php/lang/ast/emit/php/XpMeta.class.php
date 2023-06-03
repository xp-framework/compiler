<?php namespace lang\ast\emit\php;

use lang\ast\types\IsGeneric;

/**
 * Emit meta information so that the XP reflection API won't have to parse
 * it. Also omits apidoc comments and annotations from the generated code.
 * This is the only way we can add full annotation support to PHP 7 without
 * impacting the line numbers!
 *
 * Code compiled with this optimization in place requires using XP Core as
 * a dependency!
 *
 * @see  https://github.com/xp-framework/reflection/pull/27
 * @see  https://github.com/xp-framework/rfc/issues/336
 */
trait XpMeta {

  /** Stores lowercased, unnamespaced name in annotations for BC reasons! */
  protected function annotations($result, $annotations, $resolve= null) {
    if (null === $annotations) return [];

    $lookup= [];
    foreach ($annotations as $name => $arguments) {
      $p= strrpos($name, '\\');
      $key= lcfirst(false === $p ? $name : substr($name, $p + 1));
      $result->out->write("'".$key."' => ");
      $name === $key || $lookup[$key]= $name;

      if (empty($arguments)) {
        $result->out->write('null,');
      } else if (1 === sizeof($arguments) && isset($arguments[0])) {
        $this->emitOne($result, $arguments[0]);
        $result->out->write(',');
        $lookup[$name][$resolve]= 1; // Resolve ambiguity
      } else {
        $result->out->write('[');
        foreach ($arguments as $name => $argument) {
          is_string($name) && $result->out->write("'".$name."' => ");
          $this->emitOne($result, $argument);
          $result->out->write(',');
        }
        $result->out->write('],');
      }
    }
    return $lookup;
  }

  /** Emits annotations in XP format - and mappings for their names */
  private function attributes($result, $annotations, $target) {
    $result->out->write('DETAIL_ANNOTATIONS => [');
    $lookup= $this->annotations($result, $annotations);
    $result->out->write('], DETAIL_TARGET_ANNO => [');
    foreach ($target as $name => $annotations) {
      $result->out->write("'$".$name."' => [");
      foreach ($this->annotations($result, $annotations, $name) as $key => $value) {
        $lookup[$key]= $value;
      }
      $result->out->write('],');
    }
    foreach ($lookup as $key => $value) {
      $result->out->write("'{$key}' => ".var_export($value, true).',');
    }
    $result->out->write(']');
  }

  /** Emit comment inside meta information */
  private function comment($comment) {
    return null === $comment ? 'null' : var_export($comment->content(), true);
  }

  /** Emit xp::$meta */
  protected function emitMeta($result, $type, $annotations, $comment) {
    if (null === $type) {
      $result->out->write('\xp::$meta[strtr(self::class, "\\\\", ".")]= [');
    } else if ($type instanceof IsGeneric) {
      $result->out->write('\xp::$meta[\''.$type->base->name().'\']= [');
    } else {
      $result->out->write('\xp::$meta[\''.$type->name().'\']= [');
    }
    $result->out->write('"class" => [');
    $this->attributes($result, $annotations, []);
    $result->out->write(', DETAIL_COMMENT => '.$this->comment($comment).'],');

    foreach ($result->codegen->scope[0]->meta as $type => $lookup) {
      $result->out->write($type.' => [');
      foreach ($lookup as $key => $meta) {
        $result->out->write("'".$key."' => [");
        $this->attributes($result, $meta[DETAIL_ANNOTATIONS], $meta[DETAIL_TARGET_ANNO]);
        $result->out->write(', DETAIL_RETURNS => \''.$meta[DETAIL_RETURNS].'\'');
        $result->out->write(', DETAIL_COMMENT => '.$this->comment($meta[DETAIL_COMMENT]));
        $result->out->write(', DETAIL_ARGUMENTS => ['.($meta[DETAIL_ARGUMENTS]
          ? "'".implode("', '", $meta[DETAIL_ARGUMENTS])."']],"
          : ']],'
        ));
      }
      $result->out->write('],');
    }
    $result->out->write('];');
  }

  protected function emitComment($result, $comment) {
    // Omit from generated code
  }

  protected function emitAnnotation($result, $annotation) {
    // Omit from generated code
  }

  protected function emitAnnotations($result, $annotations) {
    // Omit from generated code
  }
}