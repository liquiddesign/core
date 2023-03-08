<?php

namespace Base;

use Composer\Script\Event;
use Ifsnop\Mysqldump\Mysqldump;
use Nette\Bootstrap\Configurator;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\FileNotFoundException;
use Nette\Neon\Neon;
use Nette\Security\Passwords;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use StORM\Connection;
use StORM\DIConnection;
use Tracy\Debugger;
use Tracy\ILogger;

abstract class Scripts
{
	abstract protected static function getRootDirectory(): string;
	
	abstract protected static function createConfigurator(): Configurator;
	
	abstract protected static function getAccountEntityClass(): string;
	
	public static function latteLint(Event $event): void
	{
		$container = static::createConfigurator()->createContainer();
		
		$arguments = $event->getArguments();
		
		if (!isset($arguments[0])) {
			$event->getIO()->writeError('ERROR: Missing argument path');
			
			return;
		}
		
		/** @var \Base\TemplateFactory $factory */
		$factory = $container->getByType(TemplateFactory::class);
		
		$linter = new \Latte\Tools\Linter($factory->createTemplate()->getLatte(), true);
		$ok = $linter->scanDirectory($arguments[0]);
		
		if (!$ok) {
			$event->getIO()->writeError('Nastala chyba.');
		}
		
		$event->getIO()->write('Hotovo.');
	}

	public static function clearEshopCategoriesProductCache(Event $event): void
	{
		$container = static::createConfigurator()->createContainer();

		$cache = new Cache($container->getByType(Storage::class));

		$cache->clean([
			Cache::TAGS => ['categories', 'pricelists', 'products'],
		]);

		$event->getIO()->write('Eshop categories and products cache was cleaned.');
	}

	public static function clearNetteCache(Event $event): void
	{
		static::clearCache();

		$event->getIO()->write('Nette cache was cleaned.');
	}
	
	public static function clearLog(Event $event): void
	{
		$dir = static::getRootDirectory() . '/temp/log';
		
		foreach (Finder::findFiles()->from($dir) as $key => $file) {
			unset($file);
			FileSystem::delete($key);
		}
		
		$event->getIO()->write('Log was cleaned.');
	}
	
	public static function maintenance(Event $event): void
	{
		$arguments = $event->getArguments();
		
		if (!isset($arguments[0]) || !Arrays::contains(['on', 'off'], $arguments[0])) {
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
		
		$connection->query('CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;', [], [$projectName]);
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
					$event->getIO()->writeError('ERROR: password cannot be empty');
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
				
				// @codingStandardsIgnoreLine
				foreach (Neon::decode(\str_replace(\array_keys($parameters), \array_values($parameters), \file_get_contents("$dataDir/$dataFile.neon"))) ?? [] as $values) {
					$stm->findRepository($values->value)->syncOne($values->attributes);
				}
			}
		}
		
		$event->getIO()->write('Done.');
	}
	
	public static function importProductionDatabaseToDevelop(Event $event): void
	{
		$productionDir = 'www';
		
		if (\basename(\dirname(static::getRootDirectory(), 2)) === $productionDir) {
			$event->getIO()->writeError('!! WARNING: Script is available only on develop or staging environment !!');
		}
		
		Debugger::enable(Debugger::DETECT, static::getRootDirectory() . '/temp/log');
		Debugger::log('importProductionDatabaseToDevelop - START');
		$event->getIO()->write('--- START ---');
		
		$localConfig = static::getRootDirectory() . '/config/general.local.neon';
		$productionConfig = static::getRootDirectory() . '/config/general.production.neon';
		
		$dbConfig = null;
		
		if (\is_file($localConfig)) {
			$dbConfig = Neon::decode(\file_get_contents($localConfig));
		}
		
		if (\is_file($productionConfig)) {
			$dbConfig = Neon::decode(\file_get_contents($productionConfig));
		}
		
		if (!$dbConfig) {
			Debugger::log('Error: No configuration file found!', ILogger::ERROR);
			
			return;
		}
		
		if (!isset($dbConfig['storm']['connections']['production'])) {
			$event->getIO()->writeError('!! error: Production DB is not defined in configuration file');
			
			return;
		}
		
		$filename = static::getRootDirectory() . '/temp/mysql_dump.sql';
		
		/* Dump from production DB */
		try {
			$dump = new Mysqldump(
				'mysql:host=' . $dbConfig['storm']['connections']['production']['host'] . ';dbname=' . $dbConfig['storm']['connections']['production']['dbname'],
				$dbConfig['storm']['connections']['production']['user'],
				$dbConfig['storm']['connections']['production']['password'],
				[],
				[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
			);
			
			$dump->start($filename);
			
			$event->getIO()->write('- production DB dumped');
			Debugger::log('-- production DB dumped');
		} catch (\Exception $e) {
			$event->getIO()->writeError('!! error: ' . $e->getMessage());
			Debugger::log('error: ' . $e->getMessage(), ILogger::ERROR);
		}
		
		/* Import to develop DB */
		try {
			if (!\file_exists($filename)) {
				throw new FileNotFoundException("Error: File not found.\n");
			}
			
			$fp = \fopen($filename, 'r');
			
			$templine = '';
			
			$container = static::createConfigurator()->createContainer();
			
			/** @var \StORM\DIConnection $stm */
			$stm = $container->getByType(DIConnection::class);
			$stm->exec('SET foreign_key_checks = 0;');
			$dbName = $stm->getDatabaseName();
			$dropTableList = $stm->query("SELECT concat('DROP TABLE IF EXISTS `', table_name, '`;') FROM information_schema.tables WHERE table_schema = '$dbName';")->fetchAll(\PDO::FETCH_COLUMN);
			
			foreach ($dropTableList as $dropTable) {
				$stm->exec($dropTable);
			}
			
			$stm->exec('SET foreign_key_checks = 1;');
			
			$event->getIO()->write('- develop DB tables dropped');
			
			while (($line = \fgets($fp)) !== false) {
				// Skip it if it's a comment
				if (Strings::substring($line, 0, 2) === '--' || $line === '') {
					continue;
				}
				
				$templine .= $line;
				
				// If it has a semicolon at the end, it's the end of the query
				if (Strings::substring(Strings::trim($line), -1, 1) !== ';') {
					continue;
				}
				
				$stm->query($templine);
				
				$templine = '';
			}
			
			//close the file
			\fclose($fp);
			
			$event->getIO()->write('- production DB imported to develop');
		} catch (\Throwable $e) {
			$event->getIO()->writeError('!! Error importing: ' . $e->getMessage());
			Debugger::log('Error importing: ' . $e->getMessage(), ILogger::ERROR);
		}
		
		FileSystem::delete($filename);
		
		$event->getIO()->write('--- FINISH -- ');
	}
	
	public static function importProductionUserfilesToDevelop(Event $event): void
	{
		$productionDir = 'www';
		
		if (\basename(\dirname(static::getRootDirectory(), 2)) === $productionDir) {
			$event->getIO()->writeError('!! WARNING: Script is available only on develop or staging environment !!');
		}
		
		$event->getIO()->write('--- START ---');
		
		$productionUserfilesDir = static::getRootDirectory() . "/../$productionDir/userfiles";
		$developUserfilesDir = static::getRootDirectory() . '/userfiles';
		
		FileSystem::delete($developUserfilesDir);
		
		FileSystem::copy($productionUserfilesDir, $developUserfilesDir);
		
		$event->getIO()->write('--- FINISH -- ');
	}

	public static function deployCheck(Event $event): void
	{
		$rootDir = __DIR__ . '/../../../..';

		if (\is_file($rootDir . '/maintenance.php')) {
			$event->getIO()->write('Deploy is already running ... exiting');

			die;
		}

		$deployLogPath = $rootDir . '/temp/log/deploy_log.txt';

		if (!\is_file($deployLogPath)) {
			$event->getIO()->write('No deploy logged');

			if (!$event->getIO()->askConfirmation('Do you really want to proceed? (n)', false)) {
				die;
			}

			\touch($deployLogPath);
		} else {
			$firstLog = Strings::before(FileSystem::read($deployLogPath), "\n");

			$event->getIO()->write("Last deploy logged: $firstLog");

			if (!$event->getIO()->askConfirmation('Do you really want to proceed? (n)', false)) {
				die;
			}
		}
	}

	public static function deployDone(Event $event): void
	{
		unset($event);

		$rootDir = __DIR__ . '/../../../..';
		$deployLogPath = $rootDir . '/temp/log/deploy_log.txt';

		$currentTimeString = (new \DateTime())->format('Y-m-d H:i:s');
		$lastDeployLog = FileSystem::read($deployLogPath);

		FileSystem::write($deployLogPath, $currentTimeString . "\n" . $lastDeployLog);
	}
	
	protected static function clearCache(): void
	{
		$dir = static::getRootDirectory() . '/temp/cache';
		
		foreach (Finder::findFiles()->from($dir) as $key => $file) {
			unset($file);
			FileSystem::delete($key);
		}
	}
}
