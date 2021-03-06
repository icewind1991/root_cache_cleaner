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

namespace OCA\RootCacheClean\Command;

use OC\Core\Command\Base;
use OC\Files\Storage\LocalRootStorage;
use OCA\RootCacheClean\Cleaner;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Cleanup extends Base {
	/** @var Cleaner */
	private $cleaner;
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(Cleaner $cleaner, IUserManager $userManager, IRootFolder $rootFolder) {
		parent::__construct();
		$this->cleaner = $cleaner;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
	}

	protected function configure() {
		$this
			->setName('root_cache_cleaner:clean')
			->setDescription('Clean duplicate items from the root filecache')
			->addOption('no-warning', null, InputOption::VALUE_NONE, "Disable the warning before starting the cleaning process");
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$rootStorage = $this->rootFolder->get('')->getStorage();
		if (!$rootStorage->instanceOfStorage(LocalRootStorage::class)) {
			$output->writeln("This app only works when using local storage for the root.");
			return 1;
		}
		$rootStorageId = $rootStorage->getCache()->getNumericStorageId();

		if (!$input->getOption('no-warning')) {
			$helper = $this->getHelper('question');
			$output->writeln("While the cleanup process should be safe there is still a risk involved in bulk deleting filecache entries like this.");
			$output->writeln("It is <options=underscore>strongly</> recommended to ensure that a proper database backup is in place before running this process.");
			$output->writeln("Note that this process involves some fairly heavy database queries and can take a long time on large instances.");
			$question = new ConfirmationQuestion("Continue? [y/N] ", false);

			if (!$helper->ask($input, $output, $question)) {
				return 0;
			}
		}

		$deleted = 0;
		$this->userManager->callForSeenUsers(function ($user) use ($rootStorageId, &$deleted, $output) {
			$output->write("Cleaning for user <info>" . $user->getUID() . "</info>...");
			$userDeleted = $this->cleaner->cleanForUser($rootStorageId, $user->getUID());
			;
			$output->writeln(" Done, deleted <info>$userDeleted</info> entries.");
			$deleted += $userDeleted;
		});

		$output->writeln("Deleted <info>$deleted</info> entries in total.");

		return 0;
	}
}
