<?php namespace lang\ast;

use lang\ast\emit\Reflection;
use lang\ast\emit\php\XpMeta;
use lang\{
  ClassFormatException,
  ClassLoader,
  ClassLoadingException,
  ClassNotFoundException,
  ElementNotFoundException,
  IClassLoader,
  XPClass
};

class CompilingClassLoader implements IClassLoader {
  const EXTENSION = '.php';

  private static $ignore= ['autoload.php' => true, '__xp.php' => true];
  private static $instance= [];
  private $version;
  private $source= [];

  static function __static() {

    // See https://github.com/xp-framework/compiler/issues/122
    Reflection::__static();
  }

  /** Creates a new instances with a given PHP runtime */
  private function __construct($emit) {
    $this->version= strtr($emit->getSimpleName(), ['⋈' => '+', '·' => '.']);

    Compiled::$emit[$this->version]= $emit->newInstance();
    stream_wrapper_register($this->version, Compiled::class);
  }

  /**
   * Locate a class' sourcecode
   *
   * @param  string $class
   * @return lang.IClassLoader or NULL if nothing can be found
   */
  protected function locateSource($class) {
    if (!isset($this->source[$class])) {
      $uri= strtr($class, '.', '/').self::EXTENSION;
      foreach (ClassLoader::getDefault()->getLoaders() as $loader) {
        if ($loader instanceof self) continue;
        if ($loader->providesResource($uri)) return $this->source[$class]= [substr(self::EXTENSION, 1), $loader];
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
    if (isset($this->source[$uri])) return true;

    $e= strlen(self::EXTENSION);
    if (0 !== substr_compare($uri, self::EXTENSION, -$e)) return false;
    if (0 === substr_compare($uri, \xp::CLASS_FILE_EXT, -strlen(\xp::CLASS_FILE_EXT))) return false;

    foreach (ClassLoader::getDefault()->getLoaders() as $loader) {
      if ($loader instanceof self) continue;

      $l= strlen($loader->path);
      if (0 === substr_compare($loader->path, $uri, 0, $l)) {
        $this->source[$uri]= strtr(substr($uri, $l, -$e), [DIRECTORY_SEPARATOR => '.']);
        return true;
      }
    }

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
    $r= [];
    foreach (ClassLoader::getDefault()->getLoaders() as $loader) {
      if ($loader instanceof self) continue;
      foreach ($loader->packageContents($package) as $content) {
        if (isset(self::$ignore[$content])) continue;
        if (self::EXTENSION === substr($content, $p= strpos($content, '.'))) {
          $r[]= substr($content, 0, $p).\xp::CLASS_FILE_EXT;
        }
      }
    }
    return $r;
  }

  /**
   * Find the class by a given URI
   *
   * @param   string uri
   * @return  lang.XPClass
   * @throws  lang.ClassNotFoundException in case the class can not be found
   */
  public function loadUri($uri) {
    if (!$this->providesUri($uri)) {
      throw new ClassNotFoundException('No such class at '.$uri);
    }

    $class= $this->loadClass($this->source[$uri]);
    unset($this->source[$uri]);
    return $class;
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
    $name= strtr($class, '.', '\\');
    if (isset(\xp::$cl[$class])) return $name;

    if (null === ($source= $this->locateSource($class))) {
      throw new ClassNotFoundException($class);
    }

    $uri= strtr($class, '.', '/').self::EXTENSION;
    Compiled::$source[$uri]= $source;

    \xp::$cl[$class]= nameof($this).'://'.$this->instanceId();
    \xp::$cll++;
    try {
      include($this->version.'://'.$uri);
    } catch (ClassLoadingException $e) {
      unset(\xp::$cl[$class]); // @codeCoverageIgnore
      throw $e;                // @codeCoverageIgnore
    } catch (\Throwable $e) {
      unset(\xp::$cl[$class]);
      throw new ClassFormatException('Compiler error: '.$e->getMessage(), $e);
    } finally {
      \xp::$cll--;
      unset(Compiled::$source[$uri]);
    }

    method_exists($name, '__static') && \xp::$cli[]= [$name, '__static'];
    if (0 === \xp::$cll) {
      $invocations= \xp::$cli;
      \xp::$cli= [];
      foreach ($invocations as $inv) $inv($name);
    }
    return $name;
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

    return Compiled::bytes($this->version, $source, strtr($class, '.', '/').self::EXTENSION);
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
    return $this->version;
  }

  /**
   * Fetch instance of classloader by version
   *
   * @param  string $version
   * @return lang.IClassLoader
   */
  public static function instanceFor($version) {
    sscanf($version, "%[^+]+%[^\r]", $emitter, $augmented);
    $emit= Emitter::forRuntime($emitter, $augmented ? explode('+', $augmented) : [XpMeta::class]);

    $id= $emit->getName();
    if (!isset(self::$instance[$id])) {
      self::$instance[$id]= new self($emit);
    }
    return self::$instance[$id];
  }

  /**
   * Gets a string representation
   *
   * @return string
   */
  public function toString() {
    return 'CompilingCL<'.$this->version.'>';
  }

  /**
   * Gets a hash code
   *
   * @return string
   */
  public function hashCode() {
    return 'C'.$this->version;
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? version_compare($this->version, $value->version) : 1;
  }
}