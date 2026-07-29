<?php
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
