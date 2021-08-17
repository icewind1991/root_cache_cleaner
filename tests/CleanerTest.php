<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\RootCacheCleaner\Tests;

use OC\Files\Storage\Local;
use OC\Files\Storage\Temporary;
use OCA\RootCacheClean\Cleaner;
use Test\TestCase;

/**
 * @group DB
 */
class CleanerTest extends TestCase {
	/** @var Cleaner */
	private $cleaner;

	protected function setUp(): void {
		parent::setUp();

		$this->cleaner = \OC::$server->get(Cleaner::class);
	}


	public function testCleanup() {
		$rootStorage = new Temporary([]);
		$rootStorage->mkdir('user');
		$userStorage = new Local(['datadir' => $rootStorage->getSourcePath('user')]);

		$rootStorage->mkdir('appdata');
		$rootStorage->file_put_contents('appdata/data.txt', 'data');

		$userStorage->mkdir('files');
		$userStorage->file_put_contents('files/user_file.txt', 'user_data');

		// the "proper" root scanner has safeguards against scanning user files, this fake root one doesn't
		$rootStorage->getScanner()->scan('');
		$userStorage->getScanner()->scan('');

		$rootCache = $rootStorage->getCache();
		$userCache = $userStorage->getCache();

		$this->assertNotEquals($rootCache->getNumericStorageId(), $userCache->getNumericStorageId());

		$this->assertTrue($rootCache->inCache('appdata/data.txt'));
		$this->assertTrue($rootCache->inCache('user/files'));
		$this->assertTrue($rootCache->inCache('user/files/user_file.txt'));

		$this->assertTrue($userCache->inCache('files/user_file.txt'));

		$this->assertEquals(2, $this->cleaner->cleanForUser($rootCache->getNumericStorageId(), 'user'));

		$this->assertTrue($rootCache->inCache('appdata/data.txt'));
		$this->assertFalse($rootCache->inCache('user/files'));
		$this->assertFalse($rootCache->inCache('user/files/user_file.txt'));

		$this->assertTrue($userCache->inCache('files/user_file.txt'));
	}
}
