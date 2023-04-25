<?php

namespace Base\Entity;

use Base\DB\Shop;

trait ShopEntityTrait
{
	/**
	 * @column
	 * @relation
	 * @constraint{"onUpdate":"CASCADE","onDelete":"SET NULL"}
	 */
	public ?Shop $shop = null;
}
