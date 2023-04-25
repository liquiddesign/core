<?php

namespace Base;

use Base\DB\Shop;
use Base\DB\ShopRepository;
use Nette\DI\Container;
use Nette\Http\Request;
use Nette\Utils\Strings;

class ShopsConfig
{
	protected \stdClass $config;

	private Shop|null $selectedShop;

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

	public function getShopSpecificProperties(): \stdClass
	{
		return $this->config->shopSpecificProperties;
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
		return $this->shopRepository->many()->toArray();
	}
}
