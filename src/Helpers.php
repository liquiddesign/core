<?php

namespace Base;

class Helpers
{
	/**
	 * @template T of object
	 * @param array<T> $xs
	 * @param callable(T): bool $f
	 */
	public static function arrayFind(array $xs, callable $f): ?\stdClass
	{
		foreach ($xs as $x) {
			if (\call_user_func($f, $x) === true) {
				return $x;
			}
		}

		return null;
	}
}
