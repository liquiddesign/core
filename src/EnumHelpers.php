<?php

namespace Base;

abstract class EnumHelpers
{
	/**
	 * @param array<\UnitEnum> $cases
	 * @return array<string|int>
	 */
	public static function getEnumValues(array $cases): array
	{
		return \array_column($cases, 'value');
	}
}
