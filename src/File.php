<?php
namespace vestibulum;
/**
 * Vestibulum file with metadata
 *
 * @property string id
 * @property string class
 * @property string title
 * @property string order
 * @property string date
 * @property string access
 * @property string name
 * @property string basename
 * @property string dir
 * @property string file
 * @property bool twig
 *
 * @author Roman Ožana <ozana@omdesign.cz>
 */
class File extends \SplFileInfo {

	use Metadata;

	/** @var array */
	protected $meta;

	/** @var string|null */
	protected $content;

	/** @var array */
	public $children;

	public function __construct($file = null, array $meta = [], $content = null) {
		parent::__construct($file);
		$this->meta = $this->getMeta($meta);
		$this->content = $content;
	}

	/**
	 * Return current file metadata
	 *
	 * @param array $meta
	 * @return array
	 */
	public function getMeta(array $meta = []) {
		if ($this->meta) return array_merge($meta, $this->meta);

		$title = $this->parseTitle($this->getContent()) ? : ucfirst($this->getName());

		$default = [
			'id' => md5($this->getContent() . $this->getRealPath()),
			'class' => preg_replace('/[.]/', '', strtolower($this->getName())),
			'title' => $title,
			'order' => $title,
			'date' => $this->isFile() || $this->isDir() ? $this->getCTime() : null,
			'created' => $this->isFile() || $this->isDir() ? $this->getCTime() : null,
			'access' => $this->isFile() || $this->isDir() ? $this->getATime() : null,
			'name' => $this->getName(),
			'basename' => $this->getFilename(),
			'dir' => $this->isDir() ? $this->getDir() : null,
			'file' => $this->isFile() ? $this->getRealPath() : null,
			'twig' => false, // use Twig syntax
		];

		return array_merge($default, $meta, (array)$this->parseMeta($this->getContent()));
	}

	/**
	 * Return automatic description
	 *
	 * @return mixed
	 */
	public function getDescription() {
		return isset($this->description) ? $this->description : $this->shorten($this->getContent());
	}

	/**
	 * Return link to file
	 *
	 * @param string|null $src
	 * @return string
	 */
	public function getSlug($src = null) {
		return str_replace(
			realpath($src),
			'',
			$this->isDir() ? $this->getRealPath() : $this->getDir() . '/' . ($this->getName() !== 'index' ? $this->getName(
				) : null)
		);
	}

	/**
	 * Return file metadata value
	 *
	 * @param string $name
	 * @return null
	 */
	public function __get($name) {
		return array_key_exists($name, $this->meta) ? $this->meta[$name] : null;
	}

	/**
	 * Set meta value
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->meta[$name] = $value;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function __isset($name) {
		return array_key_exists($name, $this->meta);
	}


	/**
	 * Return name of file without extension
	 *
	 * @return string
	 */
	public function getName() {
		return $this->getBasename('.' . $this->getExtension());
	}

	/**
	 * Return current directory
	 *
	 * @return string
	 */
	public function getDir() {
		return $this->isDir() ? $this->getRealPath() : dirname($this->getRealPath());
	}

	/**
	 * @param array $skip
	 * @return bool
	 */
	public function isValid(array $skip = []) {
		if ($this->isDir()) return !in_array($this->getRealPath(), $skip);
		return preg_match('#md|html#i', $this->getExtension()) && !in_array($this->getName(), $skip);
	}

	/**
	 * Return file content
	 *
	 * @return string
	 */
	public function getContent() {
		if (isset($this->content)) return $this->content;

		if ($this->isDir()) {
			return $this->content =
				is_file($file = $this . '/index.html') || is_file($file = $this . '/index.md') ? file_get_contents($file) : '';
		}

		return $this->content = $this->isFile() ? file_get_contents($this->getRealPath()) : '';
	}

	/**
	 * Set file content
	 *
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Create new File instance from path
	 *
	 * @param $request
	 * @param array $meta
	 * @return static
	 */
	public static function fromRequest($request, array $meta = []) {
		if (
			is_file($file = $request . '.html') ||
			is_file($file = $request . '.md') ||
			// is_file($file = $request . '.php') || // TODO add raw PHP support
			is_dir($request) && is_file($file = $request . '/index.html') ||
			is_dir($request) && is_file($file = $request . '/index.md')
		) {
			return new static($file, $meta);
		}
	}
}