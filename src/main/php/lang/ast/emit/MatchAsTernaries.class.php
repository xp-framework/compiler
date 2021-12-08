<?php namespace lang\ast\emit;

/**
 * Rewrites match expressions to ternaries
 *
 * @see  https://wiki.php.net/rfc/match_expression_v2
 */
trait MatchAsTernaries {

  protected function emitMatch($result, $match) {
    $t= $result->temp();
    if (null === $match->expression) {
      $result->out->write('('.$t.'=true)');
    } else {
      $result->out->write('('.$t.'=');
      $this->emitOne($result, $match->expression);
      $result->out->write(')');
    }

    $b= 0;
    foreach ($match->cases as $case) {
      foreach ($case->expressions as $expression) {
        $b && $result->out->write($t);
        $result->out->write('===(');
        $this->emitOne($result, $expression);
        $result->out->write(')?');
        $this->emitAsExpression($result, $case->body);
        $result->out->write(':(');
        $b++;
      }
    }

    // Emit IIFE for raising an error until we have throw expressions
    if (null === $match->default) {
      $result->out->write('function() use('.$t.') { throw new \\Error("Unhandled match value of type ".gettype('.$t.')); })(');
    } else {
      $this->emitAsExpression($result, $match->default);
    }
    $result->out->write(str_repeat(')', $b));
  }
}