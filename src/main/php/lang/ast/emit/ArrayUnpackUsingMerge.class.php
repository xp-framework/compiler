<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\{InstanceExpression, ScopeExpression, Literal};

/**
 * Rewrites `...` inside arrays to `array_merge()` to support unpacking
 * with string keys which throws an exception in PHP < 8.1
 *
 * @see  https://wiki.php.net/rfc/array_unpacking_string_keys
 */
trait ArrayUnpackUsingMerge {

  protected function emitArray($result, $array) {
    if (empty($array->values)) {
      $result->out->write('[]');
      return;
    }

    $unpack= false;
    foreach ($array->values as $pair) {
      $element= $pair[1] ?? $this->raise('Cannot use empty array elements in arrays');
      if ('unpack' === $element->kind) {
        $unpack= true;
        break;
      }
    }

    if ($unpack) {
      $result->out->write('array_merge([');
      foreach ($array->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }

        if ('unpack' === $pair[1]->kind) {
          if ('array' === $pair[1]->expression->kind) {
            $result->out->write('],');
            $this->emitOne($result, $pair[1]->expression);
            $result->out->write(',[');
          } else {
            $t= $result->temp();
            $result->out->write('],('.$t.'=');
            $this->emitOne($result, $pair[1]->expression);
            $result->out->write(') instanceof \Traversable ? iterator_to_array('.$t.') : '.$t.',[');
          }
        } else {
          $this->emitOne($result, $pair[1]);
          $result->out->write(',');
        }
      }
      $result->out->write('])');
    } else {
      $result->out->write('[');
      foreach ($array->values as $pair) {
        if ($pair[0]) {
          $this->emitOne($result, $pair[0]);
          $result->out->write('=>');
        }
        $this->emitOne($result, $pair[1]);
        $result->out->write(',');
      }
      $result->out->write(']');
    }
  }
}