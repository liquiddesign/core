<?php

declare(strict_types=1);

namespace Base;

use \Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$environment = (new \Nette\DI\Config\Loader)->load(__DIR__ . '/../config/environments.neon');
		
		$configurator = new \Nette\Bootstrap\Configurator();
		$configurator->setDebugMode($environment['parameters']['access']['debug'] ?? []);
		
		$configurator->setTimeZone('Europe/Prague');
		$configurator->addStaticParameters([
			'trustedMode' => $configurator->isDebugMode() || Configurator::detectDebugMode($environment['parameters']['access']['trusted']),
		]);
		
		$configurator->enableTracy(__DIR__ . '/../temp/log');
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->addConfig(__DIR__ . '/../config/general.neon');
		
		if (\is_file(__DIR__ . '/../config/general.production.neon')) {
			$configurator->addConfig(__DIR__ . '/../config/general.production.neon');
		} elseif (\is_file(__DIR__ . '/../config/general.local.neon')) {
			$configurator->addConfig(__DIR__ . '/../config/general.local.neon');
		} else {
			\trigger_error('Please run "composer init-devel or init-production"', \E_USER_ERROR );
		}
		
		return $configurator;
	}
}
