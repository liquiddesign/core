<?php

namespace Base\Entity;

trait SystemicEntityTrait
{
	/**
	 * Systemic - don´t use directly! Use methods isSystemic, addSystemic, removeSystemic.
	 * @column
	 */
	public int $systemicLock = 0;

	public function isSystemic(): bool
	{
		return $this->systemicLock > 0;
	}

	public function addSystemic(): int
	{
		$this->systemicLock++;
		$this->updateAll();

		return $this->systemicLock;
	}

	public function removeSystemic(): int
	{
		$this->systemicLock--;

		if ($this->systemicLock < 0) {
			$this->systemicLock = 0;
		} else {
			$this->updateAll();
		}

		return $this->systemicLock;
	}
}
