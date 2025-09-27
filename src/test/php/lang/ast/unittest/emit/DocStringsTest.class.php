<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

/** @see https://www.php.net/manual/en/language.types.string.php */
class DocStringsTest extends EmittingTest {

  #[Test, Values(['$body', '{$body}', '{$this->body}'])]
  public function heredoc_interpolation($variable) {
    $html= $this->run('class %T {
      private $body= "...";

      public function run() {
        $body= $this->body;

        return <<<HTML
          <html>
          <body>
            '.$variable.'
          </body>
          </html>
          HTML
        ;
      }
    }');

    Assert::equals("<html>\n<body>\n  ...\n</body>\n</html>", $html);
  }

  #[Test]
  public function nowdoc() {
    $html= $this->run('class %T {
      public function run() {
        return <<<\'HTML\'
          <html>
          <body>
            {$body}
          </body>
          </html>
          HTML
        ;
      }
    }');

    Assert::equals("<html>\n<body>\n  {\$body}\n</body>\n</html>", $html);
  }
}