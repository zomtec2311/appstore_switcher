<?php
/**
 *
 * AppStoreSwitcher APP (Nextcloud)
 *
 * @author Wolfgang Tödt <wtoedt@gmail.com>
 *
 * @copyright Copyright (c) 2026 Wolfgang Tödt
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

declare(strict_types=1);

namespace OCA\AppStoreSwitcher\Settings;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
    private IURLGenerator $urlGenerator;
    private IConfig $config;
    private IL10N $l;

    public function __construct(IURLGenerator $urlGenerator, IConfig $config, IL10N $l) {
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
        $this->l = $l;
    }

    public function getID(): string {
        return 'appstore_switcher';
    }

    public function getName(): string {
        return $this->l->t('App Store Switcher');
    }

    public function getPriority(): int {
        return 80;
    }

    public function getIcon(): string {
        return $this->urlGenerator->imagePath('appstore_switcher', 'appstore_switcher-dark.svg');
    }
}
