# ⌬ Base
Hlavní třídy a služby pro Nette

![Travis](https://travis-com.org/liquiddesign/base.svg?branch=master)

## Dokumentace
☞ [Dropbox paper](https://paper.dropbox.com/doc/Base--BSEhz5S~WSeC13vNeERAjqoKAg-Fhmi8KgiCWWOINaCfiXRa)

## TODO

Udělat base třídu pro volání scriptu z composeru

protected static Container $container;
protected static self $script;

	public static function getContainer(): Container
	{
		if (isset(self::$container)) {
			return self::$container;
		}

		self::$container = Bootstrap::boot()->createContainer();
		$class = \get_called_class();
		self::$script = new $class();
		self::$container->callInjects(self::$script);

		return self::$container;
	}

	public static function __callStatic($name, $arguments)
	{
		$container = self::getContainer();
		self::$script->runTest();
	}

	/** @inject */
	public DIConnection $storm;

	public function runTest()
	{
		var_dump($this->storm->getName());
	}