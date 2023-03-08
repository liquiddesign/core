<?php

declare(strict_types=1);

namespace Base;

use Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$dir = \dirname((new \ReflectionClass(static::class))->getFileName());
		
		$environment = (new \Nette\DI\Config\Loader())->load($dir . '/../config/environments.neon');
		
		$configurator = new \Nette\Bootstrap\Configurator();
		$configurator->setDebugMode($environment['parameters']['access']['debug'] ?? []);
		$configurator->setTimeZone('Europe/Prague');
		
		$trustedMode = $configurator->isDebugMode() || Configurator::detectDebugMode($environment['parameters']['access']['trusted']);
		$debugMode = $trustedMode ? static::getDebugModeByCookie($configurator->isDebugMode()) : $configurator->isDebugMode();
		
		$configurator->addStaticParameters([
			'trustedMode' => $trustedMode,
			'appDir' => $dir,
			'debugMode' => $debugMode,
			'productionMode' => !$debugMode,
			'maintenanceMode' => \is_file($dir . '/../maintenance.php'),
		]);
		
		$configurator->enableTracy($dir . '/../temp/log');
		$configurator->setTempDirectory($dir . '/../temp');
		$configurator->addConfig($dir . '/../config/general.neon');
		
		if (\is_file($dir . '/../config/general.production.neon')) {
			$configurator->addConfig($dir . '/../config/general.production.neon');
		} elseif (\is_file($dir . '/../config/general.local.neon')) {
			$configurator->addConfig($dir . '/../config/general.local.neon');
		} else {
			\trigger_error('Please run "composer init-devel or init-production"', \E_USER_ERROR);
		}
		
		return $configurator;
	}
	
	public static function getDebugModeByCookie(bool $default): bool
	{
		// @codingStandardsIgnoreLine
		return (bool) ($_COOKIE['debug'] ?? $default);
	}
}
