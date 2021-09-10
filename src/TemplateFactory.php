<?php

declare(strict_types=1);

namespace Base;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Localization\Translator;
use Pages\DB\Page;
use Pages\Pages;

class TemplateFactory extends \Nette\Bridges\ApplicationLatte\TemplateFactory
{
	/** @inject */
	public Pages $pages;
	
	/** @inject */
	public Application $application;
	
	/** @inject */
	public Translator $translator;
	
	/** @inject */
	public Storage $storage;
	
	public array $flagsMap;
	
	public array $mutations;
	
	public string $baseTitle;
	
	public function setTemplateParameters(Template $template): void
	{
		$this->setGlobalParameters($template);
		
		if ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->setTranslator($this->translator);
		}
		
		if (isset($template->control) && $template->control instanceof Presenter) {
			
			[$module] = \Nette\Application\Helpers::splitName($template->control->getName());
			
			\substr($module,-5) !== 'Admin' ? $this->setFrontendPresenterParameters($template) : $this->setBackendPresenterParameters($template);
		}
	}
	
	public function addFilters(Template $template): void
	{
		// IMPLEMENTED CHILD
	}
	
	protected function setGlobalParameters(Template $template): void
	{
		/** @var \stdClass $template */
		
		if (isset($template->baseUrl)) {
			$template->userUrl = $template->baseUrl . '/userfiles';
			$template->pubUrl = $template->baseUrl . '/public';
			$template->nodeUrl = $template->pubUrl . '/node_modules';
		}
	}
	
	protected function setFrontendPresenterParameters(Template $template)
	{
		$page = $this->pages->getPage();
		
		/** @var \stdClass $template */
		$template->pages = $this->pages;
		$template->page = $this->pages->getPage();
		$template->lang = $template->control->lang ?? null;
		$template->langs = $this->mutations;
		$template->ts = $this->application->getEnvironment() !== 'production' ? (new Cache($this->storage))->call('time') : \time();
		
		if ($page === null || $page instanceof Page) {
			$template->headTitle = $page ? ($page->getType() === 'index' ? $page->title : $page->title . ' | ' . $this->baseTitle) : $this->baseTitle;
			$template->headDescription = $page ? $page->description : null;
			$template->headCanonical = $page ? $page->canonicalUrl : null;
			$template->headRobots = $this->application->getEnvironment() !== 'test' ? ($page ? $page->robots : 'index, follow') : 'noindex, nofollow';
		}
	}
	
	protected function setBackendPresenterParameters(Template $template)
	{
		// IMPLEMENTED CHILD
	}
}
