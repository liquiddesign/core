<?php

namespace Base\Repository;

use Nette\Utils\Strings;
use StORM\ICollection;

abstract class GeneralRepositoryHelpers
{
	/**
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @return \StORM\ICollection<T>
	 */
	public static function selectFullName(
		ICollection $collection,
		string $selectColumnName = 'this.name',
		?string $uniqueColumnName = null,
		bool $systemic = true,
		bool $shops = true,
		bool $oldSystemicProperty = false
	): ICollection {
		$systemicCondition = 'this.systemicLock > 0';

		if ($oldSystemicProperty) {
			$systemicCondition .= ' OR this.systemic = 1';
		}

		$middleConcatenates = "$selectColumnName,
				' ',
				" . ($uniqueColumnName ? "CONCAT('(U:', $uniqueColumnName, ')')," : "'',") .
				($systemic ? "IF($systemicCondition, '(systémový)', '')," : "'',") .
				($shops ? "CONCAT('(O:', COALESCE(shop.code,'společný'), ')')," : "'',");

		$middleConcatenates = Strings::substring($middleConcatenates, 0, -1);

		return $collection->select(['fullName' => "CONCAT($middleConcatenates)"]);
	}

	/**
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @return array<string>
	 */
	public static function toArrayOfFullName(ICollection $collection, bool $selectFullName = false): array
	{
		if ($selectFullName) {
			self::selectFullName($collection);
		}

		return $collection->toArrayOf('fullName');
	}
}
