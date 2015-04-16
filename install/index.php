<?
use Bitrix\Main\Application;

require_once __DIR__.'/../lib/module.php';
require_once __DIR__.'/../lib/localization.php';
require_once __DIR__.'/../lib/options.php';
require_once __DIR__.'/../lib/moduleoptions.php';

Class ws_migrations extends CModule {
    const MODULE_ID = 'ws.migrations';
    var $MODULE_ID = 'ws.migrations';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $PARTNER_NAME = 'WorkSolutions';
    var $PARTNER_URI = 'http://worksolutions.ru';
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct() {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $localization = \WS\Migrations\Module::getInstance()->getLocalization('info');
        $this->MODULE_NAME = $localization->getDataByPath("name");
        $this->MODULE_DESCRIPTION = $localization->getDataByPath("description");
        $this->PARTNER_NAME = GetMessage('PARTNER_NAME');
        $this->PARTNER_NAME = $localization->getDataByPath("partner.name");
        $this->PARTNER_URI = 'http://worksolutions.ru';
    }

    function InstallDB($arParams = array()) {
        RegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        RegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot() . "/modules/".$this->MODULE_ID."/install/db/install.sql");
        return true;
    }

    function UnInstallDB($arParams = array()) {
        UnRegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        UnRegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot()."/modules/".$this->MODULE_ID."/install/db/uninstall.sql");
        return true;
    }

    function InstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        copy(__DIR__. $adminGatewayFile, $rootDir . $adminGatewayFile);
        return true;
    }

    function UnInstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        unlink($rootDir . $adminGatewayFile);
        return true;
    }

    function DoInstall() {
        global $APPLICATION, $data;
        $loc = \WS\Migrations\Module::getInstance()->getLocalization('setup');
        $options = \WS\Migrations\Module::getInstance()->getOptions();
        global $errors;
        $errors = array();
        if ($data['catalog']) {
            $dir = $_SERVER['DOCUMENT_ROOT'].$data['catalog'];
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            if (!is_dir($dir)) {
                $errors[] = $loc->getDataByPath('error.notCreateDir');
            }
            if (!$errors) {
                $options->catalogPath = $data['catalog'];
            }
            $this->InstallFiles();
            $this->InstallDB();
            RegisterModule(self::MODULE_ID);
            \Bitrix\Main\Loader::includeModule(self::MODULE_ID);
            \Bitrix\Main\Loader::includeModule('iblock');
            \WS\Migrations\Module::getInstance()->install();
        }
        if (!$data || $errors) {
            $APPLICATION->IncludeAdminFile($loc->getDataByPath('title'), __DIR__.'/form.php');
            return;
        }
    }

    function DoUninstall() {
        global $APPLICATION, $data;
        global $errors;
        $errors = array();
        $loc = \WS\Migrations\Module::getInstance()->getLocalization('uninstall');

        if (!$data || $errors) {
            $APPLICATION->IncludeAdminFile($loc->getDataByPath('title'), __DIR__.'/uninstall.php');
            return;
        }
        if ($data['removeAll'] == "Y") {
            $this->removeFiles();
            $this->UnInstallDB();
            $this->removeOptions();
        }
        $this->UnInstallFiles();
        UnRegisterModule(self::MODULE_ID);
    }

    private function removeFiles() {
        $options = \WS\Migrations\Module::getInstance()->getOptions();
        $dir = $_SERVER['DOCUMENT_ROOT'].$options->catalogPath;
        \Bitrix\Main\IO\Directory::deleteDirectory($dir);
    }

    private function removeOptions() {
        COption::RemoveOption("ws.migrations");
    }
}
