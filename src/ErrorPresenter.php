<?php

declare(strict_types=1);

namespace Base;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Pages\Pages;
use Tracy\ILogger;

abstract class ErrorPresenter implements Nette\Application\IPresenter
{
	use Nette\SmartObject;
	
	/** @persistent */
	public string $lang;
	
	/** @inject */
	public Pages $pages;
	
	/** @inject */
	public Http\Request $httpRequest;
	
	private ILogger $logger;
	
	private string $adminMask;
	
	public function __construct(string $adminMask, ILogger $logger)
	{
		$this->logger = $logger;
		$this->adminMask = $adminMask;
	}
	
	public function run(Nette\Application\Request $request): Nette\Application\Response
	{
		$exception = $request->getParameter('exception');
		
		if ($exception instanceof Nette\Application\BadRequestException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			$module = (new Nette\Routing\Route($this->adminMask))->match($this->httpRequest) !== null ? 'Admin' : $module;
			
			return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
		}
		
		$this->logger->log($exception, ILogger::EXCEPTION);
		
		return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (\preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
				require \dirname((new \ReflectionClass(static::class))->getFileName()) . '/templates/Error/500.phtml';
			}
		});
	}
}
