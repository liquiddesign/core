<?php

namespace Base\Entity;

use Base\DB\Shop;
use StORM\Entity;

class ShopEntity extends Entity
{
	/**
	 * @column
	 * @relation
	 * @constraint{"onUpdate":"CASCADE","onDelete":"SET NULL"}
	 */
	public ?Shop $shop = null;
}
