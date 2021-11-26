<?php

namespace Base;

use Composer\Script\Event;
use Nette\Bootstrap\Configurator;
use Nette\Neon\Neon;
use Nette\Security\Passwords;
use Nette\Utils\FileSystem;
use StORM\Connection;
use StORM\DIConnection;

abstract class Scripts
{
	abstract protected static function getRootDirectory(): string;
	
	abstract protected static function createConfigurator(): Configurator;
	
	abstract protected static function getAccountEntityClass(): string;

	public static function clearNetteCache(Event $event): void
	{
		static::clearCache();

		$event->getIO()->write('Nette cache was cleaned.');
	}
	
	public static function maintenance(Event $event): void
	{
		$arguments = $event->getArguments();
		
		if (!isset($arguments[0]) || !\in_array($arguments[0], ['on', 'off'])) {
			$event->getIO()->writeError('ERROR: Missing argument on|off!');

			return;
		}
		
		$path = static::getRootDirectory();
		
		if ($arguments[0] === 'on' && !\is_file($path . '/maintenance.php')) {
			FileSystem::rename($path . '/.maintenance.php', $path . '/maintenance.php');
		}
		
		if ($arguments[0] !== 'off' || \is_file($path . '/.maintenance.php')) {
			return;
		}

		FileSystem::rename($path . '/maintenance.php', $path . '/.maintenance.php');
	}
	
	public static function initProduction(Event $event): void
	{
		$productionConfig = static::getRootDirectory() . '/config/general.production.neon';
		
		if (\is_file($productionConfig)) {
			if (!$event->getIO()->askConfirmation('general.production.neon already exists. Override? (n)', false)) {
				return;
			}
		}
		
		$event->getIO()->write('Enter access data for the database');
		
		$config = [
			'storm' => [
				'connections' => [
					'default' => [
						'host' => (string) $event->getIO()->ask('Host:'),
						'dbname' => (string) $event->getIO()->ask('Database name:'),
						'user' => (string) $event->getIO()->ask('User:'),
						'password' => (string) $event->getIO()->ask('Password:'),
					],
				],
			],
		];
		
		\file_put_contents($productionConfig, Neon::encode($config, Neon::BLOCK));
		
		$event->getIO()->write('Done.');
	}
	
	public static function initDevel(Event $event): void
	{
		$generalConfig = static::getRootDirectory() . '/config/general.neon';
		$localConfig = static::getRootDirectory() . '/config/general.local.neon';
		$dataDir = static::getRootDirectory() . '/config/data';
		$projectName = Neon::decode(\file_get_contents($generalConfig))['parameters']['projectName'] ?? null;
		
		if (!$projectName) {
			$event->getIO()->writeError('ERROR: "projectName" parameter not found in general config!');

			return;
		}
		
		$tempDir = static::getRootDirectory() . '/temp';
		$server = 'localhost';
		
		if (!\is_file($localConfig)) {
			$dbUser = (string) $event->getIO()->ask('Enter database user (root):', 'root');
			$dbPassword = (string) $event->getIO()->ask('Enter database password:');
			
			$config = [
				'storm' => [
					'connections' => [
						'default' => [
							'host' => $server,
							'dbname' => '%projectName%',
							'user' => $dbUser,
							'password' => $dbPassword,
						],
					],
				],
			];
			
			\file_put_contents($localConfig, Neon::encode($config, Neon::BLOCK));
		} else {
			$settings = Neon::decode(\file_get_contents($localConfig))['storm']['connections']['default'];
			$dbUser = $settings['user'] ?? 'root';
			$dbPassword = $settings['password'] ?? '';
		}
		
		self::clearCache();
		
		foreach (['log', 'sessions'] as $dir) {
			FileSystem::createDir("$tempDir/$dir");
		}
		
		createDatabase:
		
		try {
			$connection = new Connection('default', "mysql:;host=$server", $dbUser, $dbPassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
		} catch (\PDOException $x) {
			$event->getIO()->writeError("ERROR: unable to join to SQL server: '$server'.");

			if (!$event->getIO()->askConfirmation('Try again? (y)')) {
				die;
			}

			goto createDatabase;
		}
		
		if (!isset($connection)) {
			return;
		}
		
		$connection->query("CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;", [], [$projectName]);
		unset($connection);
		
		$container = static::createConfigurator()->createContainer();
		
		if ($event->getIO()->askConfirmation('Do you want to sync database strucutre? (y)')) {
			\Migrator\Scripts::syncDatabase($event);
		}
		
		if ($event->getIO()->askConfirmation('Do you want to insert init data? (y)')) {
			
			/** @var \StORM\DIConnection $stm */
			$stm = $container->getByType(DIConnection::class);
			
			/** @var \Nette\Security\Passwords $passwords */
			$passwords = $container->getByType(Passwords::class);
			
			if ($stm->findRepository(static::getAccountEntityClass())->many()->where('uuid', 'servis')->isEmpty()) {
				while (!$password = $event->getIO()->askAndHideAnswer('Enter "servis" password:')) {
					$event->getIO()->writeError("ERROR: password cannot be empty");
				}
				
				$password = $passwords->hash($password);
			} else {
				$password = $stm->findRepository(static::getAccountEntityClass())->many()->where('uuid', 'servis')->firstValue('password');
			}
			
			$parameters = [
				'%password%' => "'$password'",
			];
			
			foreach ($event->getArguments() as $dataFile) {
				if (!\is_file("$dataDir/$dataFile.neon")) {
					$event->getIO()->writeError("ERROR: '$dataFile'.neon data file not found!");

					return;
				}
				
				foreach (Neon::decode(\str_replace(\array_keys($parameters), \array_values($parameters), \file_get_contents("$dataDir/$dataFile.neon"))) ?? [] as $values) {
					$stm->findRepository($values->value)->syncOne($values->attributes);
				}
			}
		}
		
		$event->getIO()->write('Done.');
	}
	
	protected static function clearCache(): void
	{
		FileSystem::delete(static::getRootDirectory() . '/temp/cache');
	}
}
