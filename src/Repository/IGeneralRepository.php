<?php

declare(strict_types=1);

namespace Base\Repository;

use StORM\Collection;

interface IGeneralRepository
{
	/**
	 * @param bool $includeHidden
	 * @return array<string>
	 */
	public function getArrayForSelect(bool $includeHidden = true): array;

	public function getCollection(bool $includeHidden = false): Collection;
}
