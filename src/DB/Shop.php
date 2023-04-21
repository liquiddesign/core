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
	 * @column
	 */
	public string $baseUrl;
}
