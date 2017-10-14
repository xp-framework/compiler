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
  private $loaders= null;

  /**
   * Locate a class' sourcecode
   *
   * @param  string $class
   * @return xp.compiler.io.Source or NULL if nothing can be found
   */
  protected function locateSource($class) {
    if (!isset($this->source[$class])) {
      $this->loaders= $this->loaders ?: ClassLoader::getDefault()->getLoaders();
      $uri= strtr($class, '.', '/').'.php';
      foreach ($this->loaders as $loader) {
        if ($loader instanceof self) continue;
        if ($loader->providesResource($uri)) return $this->source[$class]= $loader;
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
    $this->loaders= $this->loaders ?: ClassLoader::getDefault()->getLoaders();
    $r= [];
    foreach ($this->loaders as $loader) {
      if ($loader instanceof self) continue;
      foreach ($loader->packageContents($package) as $content) {
        if ('.php' === substr($content, $p= strpos($content, '.'))) {
          $r[]= substr($content, 0, $p).\xp::CLASS_FILE_EXT;
        }
      }
    }
    return $r;
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

    try {
      eval('?>'.$this->loadClassBytes($class));
    } catch (\Throwable $e) {
      throw new ClassLinkageException('Compile error', $e);
    }

    \xp::$cl[$class]= nameof($this).'://'.$this->instanceId();
    return literal($class);
  }

  /**
   * Loads class bytes
   *
   * @param  string $class
   * @return string
   * @throws lang.ClassLoadingException
   */
  public function loadClassBytes($class) {
    if (null === ($source= $this->locateSource($class))) {
      throw new ClassNotFoundException($class);  
    }

    $declaration= new MemoryOutputStream();
    $file= $source->getResourceAsStream(strtr($class, '.', '/').'.php');

    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($file->in())));
      $emitter= new Emitter($declaration);
      $emitter->emit($parse->execute());

      return $declaration->getBytes();
    } catch (Error $e) {
      throw new ClassFormatException('Syntax error', $e);
    } finally {
      $file->close();
    }
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
    return $value === $this ? 0 : 1;
  }
}
