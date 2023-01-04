<?php

declare(strict_types=1);

namespace Base;

use Nette;
use Nette\Application\IPresenterFactory;
use Nette\Routing\Router;

class Application extends \Nette\Application\Application
{
	/**
	 * @var array<callable> Occurs when entity in repository is updated
	 */
	public array $onMutationChange = [];
	
	public string $mutationRequestParameter = 'lang';
	
	private string $mutation;
	
	/**
	 * @var array<array<string>>
	 */
	private array $locales;
	
	private string $environment = 'production';

	private ?string $locale = null;
	
	public function __construct(
		array $mutations,
		array $locales,
		array $environments,
		?array $allowedHosts,
		IPresenterFactory $presenterFactory,
		Router $router,
		Nette\Http\IRequest $httpRequest,
		Nette\Http\IResponse $httpResponse
	) {
		parent::__construct($presenterFactory, $router, $httpRequest, $httpResponse);
		
		$this->locales = $locales;
		
		if (!isset($mutations[0])) {
			throw new Nette\DI\InvalidConfigurationException("Define 'mutations' parameter in config.neon");
		}
		
		$this->setMutation($mutations[0]);
		
		$this->onRequest[] = function (\Base\Application $app, Nette\Application\Request $request) use ($allowedHosts, $environments, $httpRequest): void {
			if ($allowedHosts && !Nette\Utils\Arrays::contains($allowedHosts, $httpRequest->getHeader('host'))) {
				throw new Nette\Application\BadRequestException('Not allowed HTTP HOST: ' . $httpRequest->getHeader('host'));
			}
			
			if (!$lang = $request->getParameter($app->mutationRequestParameter)) {
				return;
			}

			$app->setMutation($lang);
			
			foreach ($environments as $environment => $patterns) {
				foreach ($patterns ?? [] as $pattern) {
					if (Nette\Utils\Strings::contains($httpRequest->getUrl()->getBaseUrl(), $pattern) !== false) {
						$this->environment = $environment;

						break;
					}
				}
			}
		};
	}
	
	public function setLocale(string $mutation): void
	{
		if (isset($this->locales[$mutation])) {
			\setlocale(\LC_ALL, ...$this->locales[$mutation]);
			$this->locale = $this->locales[$mutation][0] ?? null;
		}
	}
	
	public function getLocale(): ?string
	{
		return $this->locale;
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
		$this->mutation = $mutation;
		$this->setLocale($mutation);
		
		Nette\Utils\Arrays::invoke($this->onMutationChange, $mutation);
	}
}
