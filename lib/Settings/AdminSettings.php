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

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
    private IConfig $config;
    private IInitialState $initialState;
    private string $appName = 'appstore_switcher';

    public function __construct(IConfig $config, IInitialState $initialState) {
        $this->config = $config;
        $this->initialState = $initialState;
    }

    public function getForm(): TemplateResponse {
        $currentUrl = $this->config->getSystemValueString('appstoreurl', '');
        $isEnabled = $this->config->getSystemValueBool('appstoreenabled', true);

        $historyJson = $this->config->getAppValue($this->appName, 'url_history', '[]');

        $urlHistory = json_decode($historyJson, true);

        if (!is_array($urlHistory)) {
            $urlHistory = [];
        }

        $this->initialState->provideInitialState('current_url', $currentUrl);
        $this->initialState->provideInitialState('is_enabled', $isEnabled);
        $this->initialState->provideInitialState('official_url', 'https://apps.nextcloud.com/api/v1');

        $this->initialState->provideInitialState('url_history', $urlHistory);

        return new TemplateResponse('appstore_switcher', 'admin');
    }

    public function getSection(): string {
        return 'appstore_switcher';
    }

    public function getPriority(): int {
        return 10;
    }
}
