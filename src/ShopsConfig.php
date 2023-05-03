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

	private Shop|null $selectedShop;

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

	/**
	 * Returns shop by code in GET parameter or parse from domain
	 */
	public function getSelectedShop(): Shop|null
	{
		if (isset($this->selectedShop)) {
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
				if (Strings::contains(Strings::lower($host), Strings::lower($shop->baseUrl))) {
					$selectedShop = $shop;

					break;
				}
			}
		}

		if (!$selectedShop && $debugMode) {
			$selectedShop = $this->shopRepository->many()->first();
		}

		return $this->selectedShop ??= $selectedShop;
	}

	/**
	 * @return array<\Base\DB\Shop>
	 */
	public function getAvailableShops(): array
	{
		return $this->availableShops ??= $this->shopRepository->many()->toArray();
	}

	/**
	 * Filters collection by supplied shop(s). If no shops supplied, filters by selected shop.
	 * @template T of object
	 * @param \StORM\ICollection<T> $collection
	 * @param string|array<\Base\DB\Shop|string>|null|\Base\DB\Shop $shops
	 * @param bool $showOnlyEntitiesWithSelectedShops False - Shows entities which have specified shop(s) or NULL | True - Shows only entities which have specified shop(s).
	 * @return \StORM\ICollection<T>
	 */
	public function filterShopsInShopEntityCollection(ICollection $collection, string|null|array|Shop $shops = null, bool $showOnlyEntitiesWithSelectedShops = false,): ICollection
	{
		$shopsToBeFiltered = [];

		if (!$showOnlyEntitiesWithSelectedShops) {
			$shopsToBeFiltered[] = null;
		}

		if ($shops === null) {
			$shopsToBeFiltered[] = $this->getSelectedShop()?->getPK();
		} elseif (\is_string($shops)) {
			$shopsToBeFiltered[] = $shops;
		} elseif (\is_array($shops)) {
			foreach ($shops as $shop) {
				$shopsToBeFiltered[] = \is_string($shop) ? $shop : $shop->getPK();
			}
		} elseif ($shops instanceof Shop) {
			$shopsToBeFiltered[] = $shops->getPK();
		}

		return $shopsToBeFiltered ? $collection->where('this.fk_shop', $shopsToBeFiltered) : $collection;
	}
}
