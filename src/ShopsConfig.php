<?php

namespace Base;

use Base\DB\Shop;
use Base\DB\ShopRepository;
use Nette\Http\Request;
use Nette\Utils\Strings;

class ShopsConfig
{
	protected \stdClass $config;

	private Shop|null $selectedShop;

	public function __construct(private readonly ShopRepository $shopRepository, private readonly Request $request,)
	{
	}

	public function setConfig(\stdClass $config): void
	{
		$this->config = $config;
	}

	public function getShopSpecificProperties(): \stdClass
	{
		return $this->config->shopSpecificProperties;
	}

	public function isShopRequired(): bool
	{
		return $this->config->shopRequired;
	}

	/**
	 * Returns shop by code in GET parameter or parse from domain
	 */
	public function getSelectedShop(): Shop|null
	{
		$defaultShop = $this->shopRepository->many()->first();

		if (!$defaultShop && $this->isShopRequired()) {
			throw new \Exception('Shop required by config, but no shop found.');
		}

		if (isset($this->selectedShop)) {
			return $this->selectedShop;
		}

		$code = $this->request->getQuery('shop');

		if ($code) {
			return $this->selectedShop ??= $this->shopRepository->many()->where('this.uuid', $code)->first();
		}

		$host = $this->request->getUrl()->getHost();

		foreach ($this->shopRepository->many() as $shop) {
			if (Strings::contains(Strings::lower($host), Strings::lower($shop->baseUrl))) {
				return $this->selectedShop ??= $shop;
			}
		}

		return null;
	}

	/**
	 * @return array<Shop
	 */
	public function getAvailableShops(): array
	{
		return $this->shopRepository->many()->toArray();
	}
}
