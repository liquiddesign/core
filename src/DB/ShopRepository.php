<?php

namespace Base\DB;

use Base\Repository\IGeneralRepository;
use Nette\Http\Request;
use Nette\Utils\Strings;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Base\DB\Shop>
 */
class ShopRepository extends Repository implements IGeneralRepository
{
	private Shop|null $selectedShop;

	public function __construct(DIConnection $connection, SchemaManager $schemaManager, private readonly Request $request)
	{
		parent::__construct($connection, $schemaManager);
	}

	/**
	 * @inheritDoc
	 */
	public function getArrayForSelect(bool $includeHidden = true): array
	{
		return $this->getCollection($includeHidden)->toArrayOf('name');
	}

	public function getCollection(bool $includeHidden = false): Collection
	{
		unset($includeHidden);

		return $this->many()->orderBy(['this.name']);
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
