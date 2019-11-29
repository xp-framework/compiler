<?php namespace lang\ast\unittest\emit;

use lang\{IllegalArgumentException, IllegalStateException};

/**
 * Multiple catch
 *
 * @see  https://wiki.php.net/rfc/multiple-catch
 */
class MultipleCatchTest extends EmittingTest {

  #[@test, @values([
  #  IllegalArgumentException::class,
  #  IllegalStateException::class
  #])]
  public function catch_both($type) {
    $t= $this->type('class <T> {
      public function run($t) {
        try {
          throw new $t("test");
        } catch (\\lang\\IllegalArgumentException | \\lang\\IllegalStateException $e) {
          return "caught ".get_class($e);
        }
      }
    }');

    $this->assertEquals('caught '.$type, $t->newInstance()->run($type));
  }
}