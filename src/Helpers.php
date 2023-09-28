<?php

namespace Base;

use Nette\Utils\Json;

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

	/**
	 * @param \SimpleXMLElement $xml
	 * @return array<mixed>
	 * @throws \Nette\Utils\JsonException
	 */
	public static function xmlToArray(\SimpleXMLElement $xml): array
	{
		return Json::decode(Json::encode((array) $xml), Json::FORCE_ARRAY);
	}
}
