<?php
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
