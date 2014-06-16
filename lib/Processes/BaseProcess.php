<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ApplyResult;
use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

abstract class BaseProcess {

    /**
     * @var ReferenceController
     */
    private $_referenceController;

    public function getLocalization() {
        return Module::getInstance()->getLocalization('processes');
    }

    static public function className() {
        return get_called_class();
    }

    public function __construct(ReferenceController $referenceController) {
        $this->_referenceController = $referenceController;
    }

    /**
     * @return ReferenceController
     */
    public function getReferenceController() {
        return $this->_referenceController;
    }

    abstract public function getName();

    /**
     * @param BaseSubjectHandler $subjectHandler
     * @param CollectorFix $fix
     * @param AppliedChangesLogModel $log
     * @return ApplyResult
     */
    abstract public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix, AppliedChangesLogModel $log);

    /**
     * @param BaseSubjectHandler $subjectHandler
     * @param AppliedChangesLogModel $log
     * @return ApplyResult
     */
    abstract public function rollback(BaseSubjectHandler $subjectHandler, AppliedChangesLogModel $log);
}
