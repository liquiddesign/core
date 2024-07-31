<?php

namespace Base;

use Base\DB\Shop;
use Base\DB\ShopRepository;
use Nette\DI\Container;
use Nette\Http\Request;
use Nette\Utils\Strings;
use StORM\ICollection;

class ShopsConfig
{
	protected \stdClass $config;

	private Shop|null|false $selectedShop = false;

	/**
	 * @var array<\Base\DB\Shop>
	 */
	private array $availableShops;

	public function __construct(
		private readonly ShopRepository $shopRepository,
		private readonly Request $request,
		private readonly Container $container,
	) {
	}

	public function setConfig(\stdClass $config): void
	{
		$this->config = $config;
	}

	public function setSelectedShop(Shop|string|null $shop): void
	{
		if (\is_string($shop)) {
			$shop = $this->shopRepository->one($shop, true);
		}

		$this->selectedShop = $shop;
	}

	/**
	 * Returns shop from domain parse
	 */
	public function getSelectedShopByDomain(): Shop|null
	{
		$host = $this->request->getUrl()->getHost();

		foreach ($this->shopRepository->many() as $shop) {
			$baseUrls = \explode(';', Strings::lower($shop->baseUrl));

			foreach ($baseUrls as $baseUrl) {
				if (\str_contains(Strings::lower($host), $baseUrl)) {
					return $shop;
				}
			}
		}

		return null;
	}

	/**
	 * Returns shop by code in GET parameter or parse from domain
	 */
	public function getSelectedShop(): Shop|null
	{
		if ($this->selectedShop !== false) {
			return $this->selectedShop;
		}

		$debugMode = $this->container->getParameters()['debugMode'];

		$selectedShop = null;

		$code = $this->request->getQuery('shop');

		if ($code) {
			$selectedShop = $this->shopRepository->many()->where('this.uuid', $code)->first();
		}

		$host = $this->request->getUrl()->getHost();

		if (!$selectedShop) {
			foreach ($this->shopRepository->many() as $shop) {
				$baseUrls = \explode(';', Strings::lower($shop->baseUrl));

				foreach ($baseUrls as $baseUrl) {
					if (\str_contains(Strings::lower($host), $baseUrl)) {
						$selectedShop = $shop;

						break;
					}
				}
			}
		}

		if (!$selectedShop && $debugMode) {
			$selectedShop = $this->shopRepository->many()->first();
		}

		return $this->selectedShop = $selectedShop;
	}

	/**
	 * @return array<\Base\DB\Shop>
	 */
	public function getAvailableShops(): array
	{
		return $this->availableShops ??= $this->shopRepository->many()->toArray();
	}

	/**
	 * @return array<string>
	 */
	public function getAvailableShopsArrayForSelect(): array
	{
		return $this->shopRepository->getArrayForSelect();
	}

	/**
	 * Filters collection by supplied shop(s). If no shops supplied, filters by selected shop.
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @param string|array<\Base\DB\Shop|string>|null|\Base\DB\Shop $shops
	 * @param bool $showOnlyEntitiesWithSelectedShops False - Shows entities which have specified shop(s) or NULL | True - Shows only entities which have specified shop(s).
	 * @param string $propertyName
	 * @return \StORM\ICollection<T>
	 */
	public function filterShopsInShopEntityCollection(
		ICollection $collection,
		string|null|array|Shop $shops = null,
		bool $showOnlyEntitiesWithSelectedShops = false,
		string $propertyName = 'this.fk_shop',
	): ICollection {
		$shopsToBeFiltered = [];
		$orCondition = null;

		if (!$showOnlyEntitiesWithSelectedShops) {
			$orCondition = " OR $propertyName IS NULL";
		}

		if ($shops === null) {
			if ($selectedShop = $this->getSelectedShop()) {
				$shopsToBeFiltered[] = $selectedShop->getPK();
			}
		} elseif (\is_string($shops)) {
			$shopsToBeFiltered[] = $shops;
		} elseif (\is_array($shops)) {
			foreach ($shops as $shop) {
				$shopsToBeFiltered[] = \is_string($shop) ? $shop : $shop->getPK();
			}
		} elseif ($shops instanceof Shop) {
			$shopsToBeFiltered[] = $shops->getPK();
		}

		$inString = $shopsToBeFiltered ? "'" . \implode("','", $shopsToBeFiltered) . "'" : null;

		return $inString ? $collection->where("$propertyName IN ($inString)$orCondition") : $collection;
	}

	/**
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @param string $selectColumnName
	 * @param string|null $uniqueColumnName
	 * @param bool $systemic
	 * @param bool $shops
	 * @param bool $oldSystemicProperty
	 * @param string|null $customSelect
	 * @return \StORM\ICollection<T>
	 */
	public function selectFullNameInShopEntityCollection(
		ICollection $collection,
		string $selectColumnName = 'this.name',
		?string $uniqueColumnName = null,
		bool $systemic = true,
		bool $shops = true,
		bool $oldSystemicProperty = false,
		?string $customSelect = null,
	): ICollection {
		$systemicCondition = 'this.systemicLock > 0';

		if ($oldSystemicProperty) {
			$systemicCondition .= ' OR this.systemic = 1';
		}

		$shops = $shops && $this->getAvailableShops();

		$middleConcatenates = "$selectColumnName,
				' ',
				" . ($uniqueColumnName ? "CONCAT('(U:', $uniqueColumnName, ')')," : "'',") .
			($systemic ? "IF($systemicCondition, '(systémový)', '')," : "'',") .
			($shops ? "CONCAT('(O:', COALESCE(shop.uuid,'společný'), ')')," : "'',") .
			($customSelect ? "CONCAT('(C:',$customSelect,')')," : "'',");

		$middleConcatenates = Strings::substring($middleConcatenates, 0, -1);

		return $collection->select(['fullName' => "CONCAT($middleConcatenates)"]);
	}

	/**
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @param bool $selectFullName
	 * @return array<string>
	 */
	public function shopEntityCollectionToArrayOfFullName(ICollection $collection, bool $selectFullName = false): array
	{
		if ($selectFullName) {
			$this->selectFullNameInShopEntityCollection($collection);
		}

		return $collection->toArrayOf('fullName');
	}
}
