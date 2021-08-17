<?php

namespace OCA\RootCacheClean\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('root_cache_cleaner', $urlParams);
	}
}
