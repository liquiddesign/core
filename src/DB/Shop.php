<?php

namespace Base\DB;

use StORM\Entity;

/**
 * Represents standalone shop using same database
 * @index{"name":"shop_unique_code","unique":true,"columns":["code"]}
 */
class Shop extends Entity
{
	/**
	 * @column
	 */
	public string $code;

	/**
	 * @column
	 */
	public string $name;
}
