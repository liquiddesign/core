<?php

declare(strict_types=1);

namespace Base;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Caching\Storage;
use Nette\DI\Attributes\Inject;
use Nette\Localization\Translator;
use Nette\Utils\Strings;

abstract class TemplateFactory extends \Nette\Bridges\ApplicationLatte\TemplateFactory
{
	#[Inject]
	public Application $application;
	
	#[Inject]
	public Translator $translator;
	
	#[Inject]
	public Storage $storage;

	#[Inject]
	public ShopsConfig $shopsConfig;
	
	/**
	 * @var array<string>
	 */
	public array $flagsMap;
	
	/**
	 * @var array<string>
	 */
	public array $mutations;
	
	abstract public function getBaseTitle(): string;
	
	public function addFilters(Template $template): void
	{
		unset($template);
	}
	
	protected function setGlobalParameters(Template $template): void
	{
		if (!isset($template->baseUrl)) {
			return;
		}
		
		$template->userUrl = $template->baseUrl . '/userfiles';
		$template->pubUrl = $template->baseUrl . '/public';
		$template->nodeUrl = $template->pubUrl . '/node_modules';
	}
	
	protected function setBackendPresenterParameters(Template $template): void
	{
		unset($template);
	}
}
