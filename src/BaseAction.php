<?php

namespace Abel\Actions;

use Base\Bridges\AutoWireAction;
use Nette\Utils\Arrays;

class BaseAction implements AutoWireAction
{
	/** @var array<mixed> */
	protected array $cache = [];

	protected bool $startedUp = false;

	/**
	 * @var array<callable(): void>
	 */
	protected array $onStartUp = [];

	protected function refresh(): void
	{
		$this->cache = [];
		$this->startedUp = false;

		$this->startUp();
	}

	/**
	 * Wrapper around execute function with local cache
	 * @template T
	 * @param callable(): T $dataCallback
	 * @return T
	 */
	protected function getLocalCachedOutput(string|int $index, callable $dataCallback): mixed
	{
		$this->startUp();

		if (isset($this->cache[$index])) {
			return $this->cache[$index];
		}

		return $this->cache[$index] = $dataCallback();
	}

	/**
	 * Wrapper around execute function without local cache
	 * @template T
	 * @param callable(): T $dataCallback
	 * @return T
	 */
	protected function getOutput(callable $dataCallback): mixed
	{
		$this->startUp();

		return $dataCallback();
	}

	private function startUp(): void
	{
		if ($this->startedUp) {
			return;
		}

		Arrays::invoke($this->onStartUp);

		$this->startedUp = true;
	}

	public function __invoke(): mixed
	{
		if (!\method_exists($this, 'execute')) {
			throw new \LogicException('Action must have execute method');
		}

		return $this->execute(...\func_get_args());
	}
}
