<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\ChangeDataCollector;

use Bitrix\Main\IO\File;

class Collector {
    /**
     * @var File
     */
    private $_file;

    /**
     * @var CollectorFix[]
     */
    private $_fixes;

    private $_label;

    private $_storable = true;

    private function __construct(File $file) {
        $this->_file = $file;
        $this->_label = $file->getName();
        $savedData = $this->_getSavedData();
        foreach ($savedData as $arFix) {
            $fix = $this->createFix();
            $this->registerFix($fix);
            $fix
                ->setUpdateData($arFix['data'])
                ->setOriginalData($arFix['name'])
                ->setSubject($arFix['subject'])
                ->setProcess($arFix['process'])
                ->setName($arFix['name'])
                ->setDbVersion($arFix['version']);
        }
    }

    static public function createByFile($path) {
        return new static(new File($path));
    }

    static public function createInstance($dir) {
        if (!file_exists($dir)) {
            throw new \Exception("Dir `$dir` not exists");
        }
        $fileName = time().'.json';
        return self::createByFile($dir.DIRECTORY_SEPARATOR.$fileName);
    }

    /**
     * @param array $value
     * @return $this
     */
    private function _saveData(array $value = null) {
        $this->_file->putContents(\WS\Migrations\arrayToJson($value));
        return $this;
    }

    /**
     * @return CollectorFix
     */
    public function createFix() {
        return new CollectorFix($this->_label);
    }

    public function registerFix(CollectorFix $fix) {
        $this->_fixes[] = $fix;
        return $this;
    }

    /**
     * @return array | null
     */
    private function _getSavedData() {
        if (!$this->_file->isExists()) {
            return array();
        }
        return \WS\Migrations\jsonToArray($this->_file->getContents());
    }

    /**
     * ������� ������� � ���������� ���������
     * @return $this
     */
    public function notStored() {
        $this->_storable = false;
        return $this;
    }

    /**
     * @param $dbVersion
     * @return bool
     */
    public function commit($dbVersion, $ownerName) {
        $fixesData = $this->getFixesData($dbVersion, $ownerName);
        $fixesData && $this->_storable && $this->_saveData($fixesData);
        if (!$fixesData) {
            return false;
        }
        $this->_fixes = array();
        return true;
    }

    public function getFixesData($dbVersion, $ownerName) {
        $data = array();
        foreach ($this->getUsesFixed() as $fix) {
            $data[] = array(
                'process' => $fix->getProcess(),
                'subject' => $fix->getSubject(),
                'data' => $fix->getUpdateData(),
                'originalData' => $fix->getOriginalData(),
                'name' => $fix->getName(),
                'version' => $dbVersion,
                'owner' => $ownerName
            );
        }
        if (!$data) {
            return false;
        }
        return $data;
    }

    /**
     * @return CollectorFix[]
     */
    public function getFixes() {
        return $this->_fixes;
    }

    /**
     * List of uses fixes
     * @return CollectorFix[]
     */
    public function getUsesFixed() {
        return array_filter($this->_fixes, function (CollectorFix $fix) {
            return $fix->isUses();
        });
    }
}
