<?php

namespace Base;

class ShopsConfig
{
	protected \stdClass $config;

	public function setConfig(\stdClass $config): void
	{
		$this->config = $config;
	}

	public function getShopSpecificProperties(): \stdClass
	{
		return $this->config->shopSpecificProperties;
	}
}
