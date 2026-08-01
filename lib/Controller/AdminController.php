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

namespace OCA\AppStoreSwitcher\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\HintException;
use Psr\Log\LoggerInterface;
use OC\Files\AppData\Factory;
use OCP\Files\IAppData;

class AdminController extends Controller {
    private IConfig $config;
    private IAppData $appData;

    public function __construct(Factory $appDataFactory, string $appName, IRequest $request, IConfig $config, protected LoggerInterface $logger, IAppData $appData) {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->config = $config;
        $this->appData = $appDataFactory->get('appstore');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function switchStore(string $url): DataResponse {
        try {
            if (empty($url) || $url === 'official') {
                $this->config->deleteSystemValue('appstoreurl');
                $this->config->setSystemValue('appstoreenabled', true);
                $this->logger->info('official appstore set');
                $jsonfile_within_appstorefolder = $this->config->getSystemValue('datadirectory') . '/appdata_' . $this->config->getSystemValue('instanceid') . '/appstore/apps.json';
                $this->clearCache();
                return new DataResponse(['status' => 'success', 'msg' => 'Official store activated', 'url' => '']);
            } else {
                $this->config->setSystemValue('appstoreenabled', true);
                $this->config->setSystemValue('appstoreurl', $url);

                $historyJson = $this->config->getAppValue($this->appName, 'url_history', '[]');
                $history = json_decode($historyJson, true) ?: [];

                if (!in_array($url, $history)) {
                    array_unshift($history, $url);
                    if (count($history) > 10) {
                       // array_pop($history);
                    }
                    $this->config->setAppValue($this->appName, 'url_history', json_encode($history));
                }
                // ----------------------------
                $this->logger->info('custom appstore set');
                $this->checkfile('discover.json');
                $this->checkfile('categories.json');
                $this->checkfile('apps.json');
                return new DataResponse(['status' => 'success', 'msg' => 'Custom store activated', 'url' => $url]);
            }
        } catch (HintException $e) {
            return new DataResponse(['status' => 'error', 'msg' => 'Config file is read-only. Please check permissions.'], 403);
        } catch (\Exception $e) {
            return new DataResponse(['status' => 'error', 'msg' => $e->getMessage()], 500);
        }
    }

    public function getUrlHistory(): JSONResponse {
        $historyJson = $this->config->getAppValue($this->appName, 'url_history', '[]');
        $history = json_decode($historyJson, true);

        if (!is_array($history)) {
            $history = [];
        }

        return new JSONResponse(['history' => $history]);
    }

    public function removeFromHistory(string $url): JSONResponse {
        $historyJson = $this->config->getAppValue($this->appName, 'url_history', '[]');
        $history = json_decode($historyJson, true);

        if (!is_array($history)) {
            $history = [];
        }

        $history = array_values(array_filter($history, function($item) use ($url) {
            return $item !== $url;
        }));

        $this->config->setAppValue($this->appName, 'url_history', json_encode($history));

        return new JSONResponse(['success' => true, 'history' => $history]);
    }

    public function checkfile(string $file): JSONResponse {
        $url = $this->config->getSystemValue('appstoreurl') . '/' . $file;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $this->logger->info("AppStoreSwitcher: File $url exists (HTTP 200).");
            $folder = $this->appData->getFolder('/');
            if ($folder->fileExists($file)) {
                    $folder->getFile($file)->delete();
                }
                else { $this->logger->error("AppStoreSwitcher: File $file does not exist."); }
        } elseif (in_array($httpCode, [403, 401], true)) {
            $this->logger->error("AppStoreSwitcher: File $url may exist, but access denied (HTTP $httpCode).");
        } else {
            $this->logger->error("AppStoreSwitcher: File $url probably does not exist (HTTP $httpCode).");
        }
        return new JSONResponse(['success' => true]);
    }

    private function clearCache(): void {
        try {
            $folder = $this->appData->getFolder('/');

            foreach (['discover.json', 'apps.json', 'categories.json'] as $file) {
                if ($folder->fileExists($file)) {
                    $folder->getFile($file)->delete();
                }
                else { $this->logger->error("AppStoreSwitcher: File $file does not exist."); }
            }$folder = null;
		try {
			$folder = $this->appData->getFolder('app-discover-cache');
			$folder->delete();
            $this->appData->newFolder('app-discover-cache');
		} catch (\Throwable $e) {
			$folder = $this->appData->newFolder('app-discover-cache');
		}
        } catch (NotFoundException $e) {
        }
    }
}
