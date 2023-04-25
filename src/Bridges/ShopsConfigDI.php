<?php

namespace Base\Bridges;

use Base\ShopsConfig;
use Nette;
use Nette\DI\CompilerExtension;

class ShopsConfigDI extends CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'shopRequired' => Nette\Schema\Expect::bool(false),
			'shopSpecificProperties' => Nette\Schema\Expect::arrayOf('string'),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$shopsConfig = $builder->addDefinition($this->prefix('shopsConfig'))->setType(ShopsConfig::class);
		$shopsConfig->addSetup('setConfig', [$this->getConfig()]);
	}
}
