<?php

namespace Base;

class Helpers
{
	/**
	 * @param array<\stdClass> $xs
	 * @param callable $f
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
