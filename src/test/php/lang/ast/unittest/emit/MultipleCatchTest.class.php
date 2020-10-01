<?php namespace lang\ast\unittest\emit;

use lang\{IllegalArgumentException, IllegalStateException};
use unittest\{Assert, Test, Values};

/**
 * Multiple catch
 *
 * @see  https://wiki.php.net/rfc/multiple-catch
 */
class MultipleCatchTest extends EmittingTest {

  #[Test, Values([IllegalArgumentException::class, IllegalStateException::class])]
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

    Assert::equals('caught '.$type, $t->newInstance()->run($type));
  }
}