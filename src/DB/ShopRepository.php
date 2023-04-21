<?php

namespace Base\DB;

use Nette\Http\Request;
use Nette\Utils\Strings;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Base\DB\Shop>
 */
class ShopRepository extends Repository
{
	private Shop|null $selectedShop;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, private readonly Request $request)
	{
		parent::__construct($connection, $schemaManager);
	}

	/**
	 * Returns shop by code in GET parameter or parse from domain
	 */
	public function getSelectedShop(): Shop|null
	{
		if (isset($this->selectedShop)) {
			return $this->selectedShop;
		}

		$code = $this->request->getQuery('shop');

		if ($code) {
			return $this->selectedShop ??= $this->many()->where('this.uuid', $code)->first();
		}

		$host = $this->request->getUrl()->getHost();

		foreach ($this->many() as $shop) {
			if (Strings::contains(Strings::lower($host), Strings::lower($shop->baseUrl))) {
				return $this->selectedShop ??= $shop;
			}
		}

		return null;
	}
}
