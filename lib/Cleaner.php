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

namespace OCA\RootCacheClean;

use OCP\IDBConnection;

class Cleaner {
	/**
	 * @var IDBConnection
	 */
	private $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function cleanForUser(int $rootStorageId, string $uid): int {
		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($rootStorageId)))
			->andWhere($query->expr()->like('path', $query->createNamedParameter($this->connection->escapeLikeParameter("$uid/") . '%')));
		return (int)$query->execute();
	}
}
