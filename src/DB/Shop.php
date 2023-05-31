<?php

namespace Base\DB;

use StORM\Entity;

/**
 * Represents standalone shop using same database
 */
class Shop extends Entity
{
	/**
	 * @column
	 */
	public string $name;

	/**
	 * Values separated by semicolon
	 * @column
	 */
	public string $baseUrl;
}
