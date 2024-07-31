<?php

namespace Base\Bridges;

use Base\ShopsConfig;
use Nette;
use Nette\DI\CompilerExtension;

class BaseDI extends CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'shop' => Nette\Schema\Expect::string(null),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$shopsConfig = $builder->addDefinition($this->prefix('shopsConfig'))->setType(ShopsConfig::class);
		$shopsConfig->addSetup('setConfig', [$this->getConfig()]);

		if (isset($this->getConfig()->shop)) {
			$shopsConfig->addSetup('setSelectedShop', [$this->getConfig()->shop]);
		}

		return;
	}
}
