<?php

declare(strict_types=1);

namespace Base;

use Nette;
use Nette\Application\IPresenterFactory;
use Nette\Routing\Router;
use StORM\DIConnection;

class Application extends \Nette\Application\Application
{
	private string $mutation;
	
	private array $locales;
	
	private string $environment = 'production';
	
	private DIConnection $connection;
	
	public function __construct(array $mutations, array $locales, array $environments, IPresenterFactory $presenterFactory, Router $router, Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse, DIConnection $connection)
	{
		parent::__construct($presenterFactory, $router, $httpRequest, $httpResponse);
		
		$this->connection = $connection;
		$this->locales = $locales;
		
		if (!isset($mutations[0])) {
			throw new Nette\DI\InvalidConfigurationException("Define 'mutations' parameter in config.neon");
		}
		
		$this->setMutation($mutations[0]);
		
		$this->onRequest[] = function (Application $app, Nette\Application\Request $request) use ($environments, $httpRequest): void {
			if ($lang = $request->getParameter('lang')) {
				$app->setMutation($lang);
				$app->setLocale($lang);
				
				foreach ($environments ?? [] as $environment => $patterns) {
					foreach ($patterns ?? [] as $pattern) {
						if (\strpos($httpRequest->getUrl()->getBaseUrl(), $pattern) !== false) {
							$this->environment = $environment;
							break;
						}
					}
				}
			}
		};
	}
	
	public function setLocale(string $lang): void
	{
		if (isset($this->locales[$lang])) {
			\setlocale(\LC_ALL, ...$this->locales[$lang]);
		}
	}
	
	public function getEnvironment(): string
	{
		return $this->environment;
	}
	
	public function getMutation(): string
	{
		return $this->mutation;
	}
	
	public function setMutation(string $mutation): void
	{
		$this->connection->setMutation($mutation);
		$this->mutation = $mutation;
	}
}
