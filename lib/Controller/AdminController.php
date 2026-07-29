<?php
declare(strict_types=1);

namespace OCA\AppStoreSwitcher\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\HintException;
use Psr\Log\LoggerInterface;

class AdminController extends Controller {
    private IConfig $config;

    public function __construct(string $appName, IRequest $request, IConfig $config, protected LoggerInterface $logger) {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->config = $config;
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
                if (file_exists($jsonfile_within_appstorefolder)) {
                    unlink($jsonfile_within_appstorefolder);
                }
                $jsonfile_within_appstorefolder = $this->config->getSystemValue('datadirectory') . '/appdata_' . $this->config->getSystemValue('instanceid') . '/appstore/discover.json';
                if (file_exists($jsonfile_within_appstorefolder)) {
                    unlink($jsonfile_within_appstorefolder);
                }
                $jsonfile_within_appstorefolder = $this->config->getSystemValue('datadirectory') . '/appdata_' . $this->config->getSystemValue('instanceid') . '/appstore/categories.json';
                if (file_exists($jsonfile_within_appstorefolder)) {
                    unlink($jsonfile_within_appstorefolder);
                }
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
        $url = $this->config->getSystemValue('appstoreurl') . $file;

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
            $this->logger->info("Datei $url existiert (HTTP 200).");
            $jsonfile_within_appstorefolder = $this->config->getSystemValue('datadirectory') . '/appdata_' . $this->config->getSystemValue('instanceid') . '/appstore/' . $file;
            if (file_exists($jsonfile_within_appstorefolder)) {
                unlink($jsonfile_within_appstorefolder);
            }
        } elseif (in_array($httpCode, [403, 401], true)) {
            $this->logger->error("Datei $url existiert ggf., aber Zugriff verweigert (HTTP $httpCode).");
        } else {
            $this->logger->error("Datei $url existiert wahrscheinlich nicht (HTTP $httpCode).");
        }
        return new JSONResponse(['success' => true]);
    }
}
