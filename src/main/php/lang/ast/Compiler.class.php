<?php namespace lang\ast;

use lang\XPClass;
use lang\ClassLoader;
use lang\ClassNotFoundException;
use lang\ClassFormatException;
use lang\ClassLinkageException;
use lang\ElementNotFoundException;
use text\StreamTokenizer;
use io\streams\MemoryOutputStream;

class Compiler implements \lang\IClassLoader {
  private static $instance= null;
  private $cl;

  public function __construct() {
    $this->cl= ClassLoader::getDefault()->getLoaders();
  }

  /**
   * Locate a class' sourcecode
   *
   * @param  string $class
   * @return xp.compiler.io.Source or NULL if nothing can be found
   */
  protected function locateSource($class) {
    if (!isset($this->source[$class])) {
      $uri= strtr($class, '.', '/').'.php';
      foreach ($this->cl as $cl) {
        if ($cl->providesResource($uri)) return $this->source[$class]= $cl;
      }
      return null;
    }
    return $this->source[$class];
  }

  /**
   * Checks whether this class loader provides a given uri
   *
   * @param  string $uri
   * @return bool
   */
  public function providesUri($uri) {
    return false;
  }

  /**
   * Checks whether this class loader provides a given class
   *
   * @param  string $class
   * @return bool
   */
  public function providesClass($class) {
    return null !== $this->locateSource($class);
  }

  /**
   * Checks whether this class loader provides a given resource
   *
   * @param  string $filename
   * @return bool
   */
  public function providesResource($filename) {
    return false;
  }

  /**
   * Checks whether this class loader provides a given package
   *
   * @param  string $package
   * @return bool
   */
  public function providesPackage($package) {
    return false;
  }

  /**
   * Returns a given package's contents
   *
   * @param  string $package
   * @return string[]
   */
  public function packageContents($package) {
    return [];
  }

  /**
   * Loads a class
   *
   * @param  string $class
   * @return lang.XPClass
   * @throws lang.ClassLoadingException
   */
  public function loadClass($class) {
    return new XPClass($this->loadClass0($class));
  }

  /**
   * Compiles a class if necessary
   *
   * @param  string $class
   * @return string
   * @throws lang.ClassLoadingException
   */
  public function loadClass0($class) {
    if (isset(\xp::$cl[$class])) return literal($class);

    if (null === ($source= $this->locateSource($class))) {
      throw new ClassNotFoundException($class);  
    }

    $declaration= new MemoryOutputStream();
    $file= $source->getResourceAsStream(strtr($class, '.', '/').'.php');

    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($file->in())));
      $emitter= new Emitter($declaration);
      $emitter->emit($parse->execute());

      eval('?>'.$declaration->getBytes());
    } catch (Error $e) {
      throw new ClassFormatException('Syntax error', $e);
    } catch (\Throwable $e) {
      throw new ClassLinkageException('Compile error', $e);
    } finally {
      $file->close();
    }

    \xp::$cl[$class]= nameof($this).'://'.$this->instanceId();
    return literal($class);
  }

  /**
   * Gets a resource
   *
   * @param  string $string name
   * @return string
   * @throws lang.ElementNotFoundException
   */
  public function getResource($string) {
    throw new ElementNotFoundException($string);
  }

  /**
   * Gets a resource as a stream
   *
   * @param  string $string name
   * @return io.Stream
   * @throws lang.ElementNotFoundException
   */
  public function getResourceAsStream($string) {
    throw new ElementNotFoundException($string);
  }

  /**
   * Get unique identifier for this class loader
   *
   * @return string
   */
  public function instanceId() {
    return 'compiler';
  }

  /**
   * Fetch instance of classloader by path
   *
   * @param   string path the identifier
   * @return  lang.IClassLoader
   */
  public static function instanceFor($path) {
    if (null === self::$instance) {
      self::$instance= new self();
    }
    return self::$instance;
  }

  /**
   * Gets a string representation
   *
   * @return string
   */
  public function toString() {
    return 'Compiler<>';
  }

  /**
   * Gets a string representation
   *
   * @return string
   */
  public function hashCode() {
    return spl_object_hash($this);
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return 1;
  }
}