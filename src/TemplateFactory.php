<?php

declare(strict_types=1);

namespace Base;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\DI\Attributes\Inject;
use Nette\Localization\Translator;
use Nette\Utils\Strings;
use Pages\DB\Page;
use Pages\Pages;

abstract class TemplateFactory extends \Nette\Bridges\ApplicationLatte\TemplateFactory
{
	/** @inject */
	public Pages $pages;
	
	/** @inject */
	public Application $application;
	
	/** @inject */
	public Translator $translator;
	
	/** @inject */
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
	
	public function setTemplateParameters(Template $template): void
	{
		$this->setGlobalParameters($template);
		
		if ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->setTranslator($this->translator);
		}
		
		if (!isset($template->control) || !($template->control instanceof Presenter)) {
			return;
		}

		[$module] = \Nette\Application\Helpers::splitName($template->control->getName());
		
		Strings::substring($module, -5) !== 'Admin' ? $this->setFrontendPresenterParameters($template) : $this->setBackendPresenterParameters($template);
	}
	
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
	
	protected function setFrontendPresenterParameters(Template $template): void
	{
		$page = $this->pages->getPage();
		
		$template->pages = $this->pages;
		$template->page = $this->pages->getPage();
		$template->lang = $template->control->lang ?? null;
		$template->langs = $this->mutations;
		$template->ts = $this->application->getEnvironment() === 'production' ? (new Cache($this->storage))->call('time') : \time();
		$template->shop = $this->shopsConfig->getSelectedShop();
		
		if ($page !== null && !($page instanceof Page)) {
			return;
		}

		$template->headTitle = $page ? ($page->getType() === 'index' ? $page->title : $page->title . ' | ' . $this->getBaseTitle()) : $this->getBaseTitle();
		$template->headDescription = $page ? $page->description : null;
		$template->headCanonical = $page ? $page->canonicalUrl : null;
		$template->headRobots = $this->application->getEnvironment() === 'production' ? ($page && $page->robots ? $page->robots : 'index, follow') : 'noindex, nofollow';
	}
	
	protected function setBackendPresenterParameters(Template $template): void
	{
		unset($template);
	}
}
