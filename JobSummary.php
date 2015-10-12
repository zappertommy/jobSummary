<?php

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once(JOB_MODULES.'JobModuleBase.php');

class JobSummary extends JobModuleBase {
    
    protected $data;
    
    protected $type;
    protected $label;
    protected $item;
    protected $field_val;
    protected $field_sel;
    protected $field_sub_sel;
    
    function __construct($jid) {
        parent::__construct($jid);
        
        require_once(HELPERS_PATH.'TogglePanel/TogglePanel.php');
        $this->togglePanel = new TogglePanel('jobSummaryPanel', 'left');

        $this->prepareData();
    }

    /**
     * setModuleName
     */
    protected function setModuleName() {
        $this->moduleName = "Summary";
    }

    /**
     * includeJavascript
     */
    protected function includeJavascript() {
        parent::includeJavascript();
        
        requireJavaScriptOnce($this->getLiveSiteUrl().'/components/com_jobsystem/js/date_change.js');
    }

    /**
     * renderModuleHtml
     * 
     * @return html
     */
    public function renderModuleHtml() {
        $this->includeJavascript();

        ob_start();
        ?>
        <div class="container-fluid">
            <?php
            echo $this->getClientRefItem();
            echo $this->getJobNumberItem();
            echo $this->getClientNameItem();
            echo $this->getBestPhoneItem();
            echo $this->getClientEmailItem();
            echo $this->getJobStatusItem();
            echo $this->getClaimClientItem();
            echo $this->getAssignedToItem();
            echo $this->getCaseManagerItem();
            echo $this->getEstimatorItem();
            echo $this->getJobClassificationItem();
            echo $this->getStartDateItem();
            echo $this->getCompletionDateItem();
            echo $this->getEventCodeItem();
            echo $this->getBusinessLineItem();
            ?>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        
        $this->togglePanel->setHeader($this->getEstNo()->getItem());
        $this->togglePanel->setSideDesc(
            $this->getEstNo()->getItem()
            .' | '
            .$this->getClientInfo()->getItem()
            .' | '
            .$this->getJobStatus()->getItem()
        );

        echo $this->togglePanel->render($html);
    }
    
    /**
     * prepareData
     */
    protected function prepareData() {
        $this->setData('job_details', $this->retrieveJobSummary($this->jid));
        $this->setData('business_line', $this->retrieveBusinessLine());
        
        $this->setData('users', $this->retrieveUsers());
        $this->setData('estimators', $this->retrieveEstimators());
        $this->setData('e_codes', $this->retrieveEventCodes());
        $this->setData('case_managers', $this->retrieveCaseManagers());
        $this->setData('reasons', $this->retrieveDateChangeReason());
        $this->setData('building_status', 
            $this->retrieveJobStatus(
                $this->getDataItem('job_details', 'status')
            ));
        $this->setData('users_empty', $this->retrieveUsersEmpty());
        $this->setData('business_lines', $this->retrieveBusinessLines());
    }

    /**
     * retrieveJobSummary
     * 
     * @param integer $jid
     * @return objects
     */
    protected function retrieveJobSummary($jid) {
        $qry = "
                SELECT
                    jt.id AS job_id,
                    jt.status AS building_status, 
                    jt.claim_type,
                    jt.assref,
                    jt.jobno,
                    jt.estno,
                    jt.client,
                    jt.claim_client, 
                    jt.assigned,
                    jt.estimator,
                    jt.status,
                    jt.case_manager,
                    jt.p_estno, 
                    jt.p_jobno,
                    jt.p_assref,
                    jt.start,
                    jt.complete,
                    jt.e_code, 
                    cc.title AS cc_title,
                    cc.firstname AS cc_firstname,
                    cc.surname AS cc_surname,
                    cc.email AS cc_email,
                    cc.addst AS cc_addst,
                    ccc.cname AS ccc_name,
                    au.name AS au_name,
                    esc.cname AS esc_cname,
                    UCASE(jts.name) AS jts_name,
                    ec.code_name AS ec_code_name,
                    cmu.name AS cmu_name,
                    jc.name AS jc_name,
                    IF(cc.bestphone = 1, cc.phonework, IF(cc.bestphone = 2, cc.phoneah, IF(cc.bestphone = 3, cc.mobile, ''))) AS best_phone
                FROM
                    #__job_task AS jt 
                LEFT JOIN
                    #__job_con AS cc
                    ON cc.id = jt.client 
                LEFT JOIN
                    #__job_con AS ccc
                    ON ccc.id = jt.claim_client 
                LEFT JOIN
                    #__users AS au
                    ON au.id = jt.assigned 
                LEFT JOIN
                    #__job_con AS esc
                    ON esc.id = jt.estimator 
                LEFT JOIN
                    #__job_task_status AS jts
                    ON jts.id = jt.status AND jts.publish = 1 
                LEFT JOIN
                    #__job_event_codes AS ec
                    ON ec.code_id = jt.e_code 
                LEFT JOIN
                    #__users AS cmu
                    ON cmu.id = jt.case_manager 
                LEFT JOIN
                    #__job_classification AS jc
                    ON jc.id = jt.job_classification 
                WHERE jt.id = ".(int)$jid;

        $this->getReadDatabase()->setQuery($qry);
        $this->getReadDatabase()->loadObject($obj);

        return $obj;
    }

    /**
     * retrieveUsers
     * 
     * @return objects
     */
    protected function retrieveUsers() {
        $qry = sprintf("SELECT u.id AS value, u.name AS text 
            FROM #__users AS u
            LEFT JOIN #__job_assist_users AS jau ON u.id = jau.joomla_id
            WHERE jau.company_id = %d AND jau.block = 0
            AND jau.group_id IN (
                SELECT group_id FROM #__job_assist_groups WHERE ordering >= %d
            )
            ORDER BY u.name ASC", $this->getCompanyId(), $this->getUserGroupOrder());
        $this->getReadDatabase()->setQuery($qry);
        $obj = $this->getReadDatabase()->loadObjectList();

        return $obj;
    }
    
    /**
     * retrieveUsersEmpty
     * 
     * @return array
     */
    protected function retrieveUsersEmpty() {
        $all_users = array();
        $users = $this->retrieveUsers();
        
        $all_users[] = mosHTML::makeOption('0', '- Select User -');

        if (is_array($users) && count($users) > 0) {
            $all_users = array_merge($all_users, $users);
        }

        return $all_users;
    }

    /**
     * retrieveEstimators
     * 
     * @return array
     */
    protected function retrieveEstimators() {
        $all_users = array();

        $qry = sprintf("SELECT id AS value, cname AS text FROM #__job_con 
            WHERE usertype = 8 AND blocked = 0 ORDER BY cname");
        $this->getReadDatabase()->setQuery($qry);
        $estimators = $this->getReadDatabase()->loadObjectList();

        $all_users[] = mosHTML::makeOption('0', 'N/A');

        if (is_array($estimators) && count($estimators) > 0) {
            $all_users = array_merge($all_users, $estimators);
        }

        return $all_users;
    }
    
    /**
     * retrieveEventCodes
     * 
     * @return array
     */
    protected function retrieveEventCodes() {
        $all_codes = array();

        $qry = sprintf("SELECT code_id AS value, code_name AS text FROM #__job_event_codes
            WHERE publish = 1 AND (provider_id != 'stream' OR provider_id is null) AND company_id %s ORDER BY code_name",
            ($this->getLinkECodes() != '') ? "IN (".$this->getCompanyId().",".$this->getLinkECodes().")" : "= ".$this->getCompanyId());
        $this->getReadDatabase()->setQuery($qry);
        $e_codes = $this->getReadDatabase()->loadObjectList();
        
        $all_codes[] = mosHTML::makeOption('0', '- Choose Event Code -');

        if (is_array($e_codes) && count($e_codes) > 0) {
            $all_codes = array_merge($all_codes, $e_codes);
        }

        return $all_codes;
    }

    /**
     * retrieveCaseManagers
     * 
     * @return array
     */
    protected function retrieveCaseManagers() {
        $all_users = array();
        $case_managers = array();

        $qry = sprintf("SELECT config_text FROM #__job_configuration WHERE config_short = 'case_manager_list'");
        $this->getReadDatabase()->setQuery($qry);
        $case_manager_string = $this->getReadDatabase()->loadResult();

        if(!($case_manager_string == 'case_manager_list=' || $case_manager_string == 'supervisors=')) {
            $qry = sprintf("SELECT id AS value, name AS text FROM #__users WHERE id IN (%s) ORDER BY name",
                preg_replace('/case_manager_list=|supervisors=/', '', $case_manager_string));
            $this->getReadDatabase()->setQuery($qry);
            $case_managers = $this->getReadDatabase()->loadObjectList();
        }

        $all_users[] = mosHTML::makeOption('0', 'General Claim');

        if (is_array($case_managers) && count($case_managers) > 0) {
            $all_users = array_merge($all_users, $case_managers);
        }

        return $all_users;
    }
    
    /**
     * retrieveDateChangeReason
     * 
     * @return array
     */
    protected function retrieveDateChangeReason() {
        $qry = sprintf("SELECT config_id AS value, config_text AS text FROM #__job_task_report_config 
            WHERE which = 'WORK_DATE_CHANGE_REASON' AND company_id = ".$this->getCompanyId()." AND publish = 1 
            ORDER BY ordering ");
        $this->getProviderDatabase()->setQuery($qry);
        $all_results = $this->getProviderDatabase()->loadObjectList();

        return $all_results;
    }

    /**
     * retrieveJobStatus
     * 
     * @param integer $current_status
     * @return array
     */
    protected function retrieveJobStatus($current_status) {
        $all_status = array();

        if ($current_status != '') {
            if ($current_status > 0) {
                $qry = sprintf("SELECT message FROM #__job_task_status WHERE id = %d", $current_status);
                $this->getReadDatabase()->setQuery($qry);
                $this->getReadDatabase()->loadObject($d_statuses);
                
                $d_statuses->message = trim($d_statuses->message);
                if (strlen($d_statuses->message) > 0) {
                    $d_statuses->message = str_replace("~", ",", $d_statuses->message);
                    
                    if (strlen($d_statuses->message) > 0) {
                        $where[] = " id IN (".$d_statuses->message.")";
                    }
                } else {
                    $where[] = sprintf(" type IN (%s)", $biz_type);
                }

            } elseif ($biz_type != '') {
                $where[] = sprintf(" type IN (%s)", $biz_type);
            }

            $where[] = " publish = 1";

            if (count($where) > 0){
                $where_str = " WHERE ".implode(" AND ", $where);
            }

            $d_qry = sprintf("SELECT id AS value, name AS text FROM #__job_task_status %s ORDER BY ordering", $where_str);
            $this->getReadDatabase()->setQuery($d_qry);
            $status = $this->getReadDatabase()->loadObjectList();

            $all_status[] = mosHTML::makeOption('0', '- Select Status -');

            if (is_array($status) && count($status) > 0) {
                $all_status = array_merge($all_status, $status);
            }
        }

        return $all_status;
    }
    
    /**
     * retrieveBusinessLines
     * 
     * @return array
     */
    protected function retrieveBusinessLines() {
        $all_lines = array();
        $qry = sprintf("SELECT company_id AS value, company_name AS text 
            FROM #__job_assist_company 
            WHERE company_parameters LIKE '%s' AND company_parameters NOT LIKE '%s'",
            "%business_line=".$this->getCompanyId()."%", "%bl_unpublish=".$this->getCompanyId()."%");
        $this->getReadDatabase()->setQuery($qry);
        $business_lines = $this->getReadDatabase()->loadObjectList();

        if (is_array($business_lines) && count($business_lines) > 0) {
            $all_lines = array_merge($all_lines, $business_lines);
        }

        return $all_lines;
    }

    /**
     * retrieveBusinessLine
     * 
     * @return objects
     */
    protected function retrieveBusinessLine() {
        $qry = sprintf("SELECT company_id FROM #__job_task WHERE id = '%d'", $this->getDataItem('job_details', 'job_id'));
        $this->getReadDatabase()->setQuery($qry);
        $company_id = $this->getReadDatabase()->loadResult();

        $qry = "SELECT COUNT(0) AS cnt
            FROM #__job_assist_company 
            WHERE company_parameters LIKE '%business_line=".$company_id."%'
            AND company_parameters NOT LIKE '%bl_unpublish=".$company_id."%'";
        $this->getReadDatabase()->setQuery($qry);
        $total = $this->getReadDatabase()->loadResult();

        $obj = null;

        if ($total > 1) {
            $qry = sprintf("SELECT business_line FROM #__job_task_details WHERE jid = '%d'", $this->getDataItem('job_details', 'job_id'));
            $this->getReadDatabase()->setQuery($qry);
            $this->getReadDatabase()->loadObject($results);

            if (!is_object($results) || !isset($results->business_line) || $results->business_line == 0 || $results->business_line == null) {
                $where = sprintf("WHERE company_id = '%d'", $company_id);
            } else {
                $where = sprintf("WHERE company_id = '%d'", $results->business_line);
            }

            $qry = sprintf("SELECT company_id AS id, company_name AS name FROM #__job_assist_company %s", $where);
            $this->getReadDatabase()->setQuery($qry);
            $this->getReadDatabase()->loadObject($obj);
        }

        return $obj;
    }
    
    /**
     * getBusinessLine
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getBusinessLine() {
        if ($this->getType() != 'business_line') {
            $business_line = $this->getDataGroup('business_line');
            $business_lines = $this->getDataGroup('business_lines');

            $prop = array('type' => 'business_line', 'label' => 'Business Line', 'item' => '', 
                'field_val' => '', 'field_sel' => $business_lines);

            if ($business_line != null) {
                $prop['item'] = $business_line->name;
                
                $prop['field_val'] = $business_line->id;
            }

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getCompletionDate
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getCompletionDate() {
        if ($this->getType() != 'completion_date') {
            $job_details = $this->getDataGroup('job_details');
            $reasons = $this->getDataGroup('reasons');

            $prop = array('type' => 'completion_date', 'label' => 'Completion Date', 'item' => '', 'field_sel' => $reasons);
            
            if ($job_details->complete != '0000-00-00 00:00:00') {
                $prop['item'] = date('d/m/Y', strtotime($job_details->complete));
            }

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getStartDate
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getStartDate() {
        if ($this->getType() != 'start_date') {
            $job_details = $this->getDataGroup('job_details');
            $reasons = $this->getDataGroup('reasons');

            $prop = array('type' => 'start_date', 'label' => 'Start Date', 'item' => '', 'field_sel' => $reasons);

            if ($job_details->start != '0000-00-00 00:00:00') {
                $prop['item'] = date('d/m/Y', strtotime($job_details->start));
            }

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getJobClassification
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getJobClassification() {
        if ($this->getType() != 'job_classification') {
            $job_details = $this->getDataGroup('job_details');

            $prop = array('type' => 'job_classification', 'label' => 'Job Classification', 'item' => $job_details->jc_name);

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getCaseManagerName
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getCaseManagerName() {
        if ($this->getType() != 'case_manager') {
            $job_details = $this->getDataGroup('job_details');
            $case_managers = $this->getDataGroup('case_managers');

            $prop = array('type' => 'case_manager', 'label' => 'Repair Coordinator', 'item' => '', 
                'field_val' => '', 'field_sel' => $case_managers);

            $prop['item'] = ($job_details->cmu_name != '') ? $job_details->cmu_name : 'General Claim';
            $prop['field_val'] = $job_details->case_manager;

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getEventCode
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getEventCode() {
        if ($this->getType() != 'e_code') {
            $job_details = $this->getDataGroup('job_details');
            $e_codes = $this->getDataGroup('e_codes');

            $prop = array('type' => 'e_code', 'label' => 'Event Code', 'item' => '', 'field_val' => 0, 'field_sel' => $e_codes);

            $prop['item'] = ($job_details->ec_code_name) ? $job_details->ec_code_name : '';

            $prop['field_val'] = ($job_details->e_code > 0) ? $job_details->e_code : 0;

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getJobStatus
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getJobStatus() {
        if ($this->getType() != 'status') {
            $job_details = $this->getDataGroup('job_details');
            $building_status = $this->getDataGroup('building_status');
            $users = $this->getDataGroup('users_empty');

            $prop = array('type' => 'status', 'label' => 'Job Status', 'item' => '', 'field_val' => 0, 
                'field_sel' => $building_status, 'field_sub_sel' => $users);

            $prop['item'] = $job_details->jts_name;
            $prop['field_val'] = ($job_details->status > 0) ? $job_details->status : 0;

            $this->setVars($prop);
        }
        
        return $this;
    }

    /**
     * getEstimatorName
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getEstimatorName() {
        if ($this->getType() != 'estimator') {
            $job_details = $this->getDataGroup('job_details');
            $estimators = $this->getDataGroup('estimators');

            $prop = array('type' => 'estimator', 'label' => 'Estimator/Supervisor', 'item' => '', 
                'field_val' => '', 'field_sel' => $estimators);

            $prop['item'] = ($job_details->esc_cname != '') ? $job_details->esc_cname : 'N/A';

            $prop['field_val'] = $job_details->estimator;

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getAssignedName
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getAssignedName() {
        if ($this->getType() != 'assigned_name') {
            $job_details = $this->getDataGroup('job_details');
            $users = $this->getDataGroup('users');

            $prop = array('type' => 'assigned_name', 'label' => 'Assigned To', 'item' => '', 
                'field_val' => '', 'field_sel' => $users);

            $prop['item'] = $job_details->au_name;
            $prop['field_val'] = $job_details->assigned;

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getClaimClientName
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getClaimClientName() {
        if ($this->getType() != 'claim_client_name') {
            $claim_client = $this->getDataGroup('job_details');

            $prop = array('type' => 'claim_client_name', 'label' => 'Client', 'item' => $claim_client->ccc_name);

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getClientEmail
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getClientEmail() {
        if ($this->getType() != 'client_email') {
            $client = $this->getDataGroup('job_details');

            $prop = array('type' => 'client_email', 'label' => 'Email', 'item' => '');
            $prop['item'] = ($client->cc_email != '') ? $client->cc_email : 'No Email';

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getClientName
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getClientName() {
        if ($this->getType() != 'client_name') {
            $client = $this->getDataGroup('job_details');

            $prop = array('type' => 'client_name', 'label' => 'Name', 
                'item' => $client->cc_title.' '.$client->cc_firstname.' '.$client->cc_surname);

            $this->setVars($prop);
        }

        return $this;
    }

    protected function getBestPhone() {
        if ($this->getType() != 'best_phone') {
            $client = $this->getDataGroup('job_details');

            $prop = array('type' => 'best_phone', 'label' => 'Best Phone', 'item' => $client->best_phone);

            $this->setVars($prop);
        }

        return $this;
    }
    /**
     * getJobNo
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getJobNo() {
        if ($this->getType() != 'job_no') {
            $job_details = $this->getDataGroup('job_details');

            $prop = array('type' => 'job_no', 'label' => 'Job No', 'item' => '', 'field_val' => null);

            if (isset($job_details->estno) && strlen($job_details->estno) > 0) {
                $prop['item'] = ($this->isJobNoEnabled()) ? ' / ' : '';
                $prop['item'] .= $job_details->estno.' / '.$job_details->assref;
            }

            if ($this->isJobNoEnabled()) {
                $prop['field_val'] = ($job_details->jobno) ? $job_details->jobno : '';
            }

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getClientRef
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getClientRef() {
        if ($this->getType() != 'client_ref') {
            $job_details = $this->getDataGroup('job_details');

            $prop = array('type' => 'client_ref', 'label' => '', 'item' => '');

            if ($job_details->claim_type && $job_details->claim_type == 'service_provider') {
                $prop['label'] = 'Job References';
                $prop['item'] = $job_details->p_estno;
                if ($job_details->p_jobno) {
                    $prop['item'] .= ', '.$job_details->p_jobno;
                }
                if ($job_details->p_assref) {
                    $prop['item'] .= ', '.$job_details->p_assref;
                }
            } else if($job_details->claim_type && $job_details->claim_type == 'insurer') {
                $prop['label'] = 'Job Reference';
                $prop['item'] = $job_details->assref;
            }

            $this->setVars($prop);
        }

        return $this;
    }
    
    /**
     * getClientInfo
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getClientInfo() {
        if ($this->getType() != 'client_info') {
            $client = $this->getDataGroup('job_details');

            $prop = array('type' => 'client_info', 'item' => '');

            $prop['item'] = $client->cc_title.' '.$client->cc_firstname.' '.$client->cc_surname.' | '.$client->cc_addst;

            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * getEstNo
     * 
     * chain method
     * @return \JobSummary
     */
    protected function getEstNo() {
        if ($this->getType() != 'est_no') {
            $job_details = $this->getDataGroup('job_details');
            $prop = array('type' => 'est_no', 'item' => $job_details->estno);
            $this->setVars($prop);
        }

        return $this;
    }

    /**
     * setData
     * 
     * @param string $group
     * @param objects $data
     */
    protected function setData($group, $data) {
        $this->data->{$group} = $data;
    }

    /**
     * getDataGroup
     * 
     * @param string $group
     * @return objects
     */
    protected function getDataGroup($group) {

        return $this->data->{$group};
    }

    /**
     * getDataItem
     * 
     * @param string $group
     * @param string $item
     * @return string/number
     */
    protected function getDataItem($group, $item) {

        return $this->data->{$group}->{$item};
    }

    /**
     * setVars
     * 
     * @param array $vars
     */
    protected function setVars($vars) {
        foreach ($vars as $index => $var) {
            $str = explode('_', $index);
            if ($str[0] == 'is') {
                $index = str_replace('is', '', $index);
            }

            $tmp = str_replace('_', ' ', $index);
            $tmp = ucwords($tmp);
            $tmp = str_replace(' ', '', $tmp);

            $this->{'set'.$tmp}($var);
        }
    }

    /**
     * isJobNoEnabled
     * 
     * @return boolean
     */
    protected function isJobNoEnabled() {
        
        return ($GLOBALS['current_company']->enable_jobno == 1) ? true : false;
    }

    /**
     * getCompanyId
     * 
     * @return integer
     */
    protected function getCompanyId() {

        return $GLOBALS['current_company']->company_id;
    }

    /**
     * getLinkECodes
     * 
     * @return string/number
     */
    protected function getLinkECodes() {
        $ret = '';
        if (isset($GLOBALS['current_company']->link_e_codes) && intval($GLOBALS['current_company']->link_e_codes) > 0) {
            $ret = $GLOBALS['current_company']->link_e_codes;
        }

        return $ret;
    }
    
    /**
     * getUserGroupOrder
     * 
     * @return integer
     */
    protected function getUserGroupOrder() {

        return $GLOBALS['assist_user']->group_order;
    }

    /**
     * getUserCompanyId
     * 
     * @return integer
     */
    protected function getUserCompanyId() {

        return $GLOBALS['assist_user']->company_id;
    }

    /**
     * setType
     * 
     * @param string $val
     */
    protected function setType($val) {
        $this->type = $val;
    }

    /**
     * getType
     * 
     * @return string
     */
    protected function getType() {

        return $this->type;
    }

    /**
     * setLabel
     * 
     * @param string $val
     */
    protected function setLabel($val) {
        $this->label = $val;
    }

    /**
     * getLabel
     * 
     * @return string
     */
    protected function getLabel() {

        return $this->label;
    }
    
    /**
     * setItem
     * 
     * @param string/number $val
     */
    protected function setItem($val) {
        $this->item = $val;
    }

    /**
     * getItem
     * 
     * @return string/number
     */
    protected function getItem() {

        return $this->item;
    }
    
    /**
     * setFieldVal
     * 
     * @param string/number $val
     */
    protected function setFieldVal($val) {
        $this->field_val = $val;
    }

    /**
     * getFieldVal
     * 
     * @return string/number
     */
    protected function getFieldVal() {

        return $this->field_val;
    }
    
    /**
     * setFieldSel
     * 
     * @param array $val
     */
    protected function setFieldSel($val) {
        $this->field_sel = $val;
    }
    
    /**
     * getFieldSel
     * 
     * @return array
     */
    protected function getFieldSel() {
        return $this->field_sel;
    }
    
    /**
     * setFieldSubSel
     * 
     * @param array $val
     */
    protected function setFieldSubSel($val) {
        $this->field_sub_sel = $val;
    }
    
    /**
     * getFieldSubSel
     * 
     * @return array
     */
    protected function getFieldSubSel() {

        return $this->field_sub_sel;
    }

    /**
     * setRenderThis
     */
    protected function setRenderThis() {
        $this->renderThis = true;
    }

    /**
     * getBestPhoneItem
     * 
     * @return string html
     */
    protected function getBestPhoneItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getBestPhone()->getLabel();?></label>
            <p class="form-control-static field-val"><?php echo $this->getBestPhone()->getItem(); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getClientNameItem
     * 
     * @return string html
     */
    protected function getClientNameItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getClientName()->getLabel();?></label>
            <p class="form-control-static field-val"><?php echo $this->getClientName()->getItem(); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getClientRefItem
     * 
     * @return string html
     */
    protected function getClientRefItem() {
        ob_start();

        if ($this->getClientRef()->getItem() != '') {
            ?>
            <div class="form-group">
                <label><?php echo $this->getClientRef()->getLabel(); ?></label>
                <p class="form-control-static field-val"><?php echo $this->getClientRef()->getItem(); ?></p>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * getJobNumberItem
     * 
     * @return string html
     */
    protected function getJobNumberItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getJobNo()->getLabel(); ?></label>
            <?php
            if (!is_null($this->getJobNo()->getFieldVal())) {
                ?>
                <div class="field-editable row no-gutter" data-type="jobno" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                    <div class="col-sm-2">
                        <button type="button" class="field-edit btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </button>
                    </div>
                    <div class="col-sm-9">
                        <input type="text" class="form-control field-input hidden" name="txt_job_no" value="<?php echo $this->getJobNo()->getFieldVal(); ?>" />
                        <div class="field-btns hidden">
                            <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                            <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                        </div>
                        <div class="field-val">
                            <span class="curr-val" title="Job No.">
                                <?php echo ($this->getJobNo()->getFieldVal() != '') ? $this->getJobNo()->getFieldVal() : '<strong>??Add Ref.??</strong>'; ?>
                            </span>
                            <span class="curr-txt"><?php echo $this->getJobNo()->getItem(); ?></span>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                ?><p class="form-control-static field-val"><?php echo $this->getJobNo()->getItem(); ?></p><?php
            }
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getClientEmailItem
     * 
     * @return string html
     */
    protected function getClientEmailItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getClientEmail()->getLabel();?></label>
            <p class="form-control-static field-val"><?php echo $this->getClientEmail()->getItem(); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getJobStatusItem
     * 
     * @return string html
     */
    protected function getJobStatusItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getJobStatus()->getLabel(); ?></label>
            <div class="field-editable field-status row no-gutter" data-type="status" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <div class="sub-field-group hidden">
                        <label>Current Status</label>
                        <p class="curr-txt"><?php echo $this->getJobStatus()->getItem(); ?></p>
                        <label>New Status</label>
                        <select name="sel_status" class="form-control field-input input-sm">
                            <?php foreach ($this->getJobStatus()->getFieldSel() as $opt) { ?>
                                <?php
                                    $selected = ($this->getJobStatus()->getFieldVal() == $opt->value) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                    <?php echo $opt->text; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <label>Notify Users</label>
                        <select class="form-control sub-field-input input-sm">
                            <?php foreach ($this->getJobStatus()->getFieldSubSel() as $opt) { ?>
                                <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                    <?php echo $opt->text; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val hidden"><?php echo $this->getJobStatus()->getFieldVal(); ?></span>
                        <span class="curr-txt"><?php echo $this->getJobStatus()->getItem(); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }

    /**
     * getClaimClientItem
     * 
     * @return string html
     */
    protected function getClaimClientItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getClaimClientName()->getLabel();?></label>
            <p class="form-control-static field-val"><?php echo $this->getClaimClientName()->getItem(); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getAssignedToItem
     * 
     * @return string html
     */
    protected function getAssignedToItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getAssignedName()->getLabel(); ?></label>
            <div class="field-editable row no-gutter" data-type="assigned" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm ">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <select name="sel_assigned" class="form-control field-input input-sm hidden">
                        <?php foreach ($this->getAssignedName()->getFieldSel() as $opt) { ?>
                            <?php
                                $selected = ($this->getAssignedName()->getFieldVal() == $opt->value) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                <?php echo $opt->text; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val hidden"><?php echo $this->getAssignedName()->getFieldVal(); ?></span>
                        <span class="curr-txt"><?php echo $this->getAssignedName()->getItem(); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getCaseManagerItem
     * 
     * @return string html
     */
    protected function getCaseManagerItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getCaseManagerName()->getLabel(); ?></label>
            <div class="field-editable row no-gutter" data-type="case_manager" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <select name="sel_case_manager" class="form-control field-input input-sm hidden">
                        <?php foreach ($this->getCaseManagerName()->getFieldSel() as $opt) { ?>
                            <?php
                                $selected = ($this->getCaseManagerName()->getFieldVal() == $opt->value) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                <?php echo $opt->text; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val hidden"><?php echo $this->getCaseManagerName()->getFieldVal(); ?></span>
                        <span class="curr-txt"><?php echo $this->getCaseManagerName()->getItem(); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getEstimatorItem
     * 
     * @return string html
     */
    protected function getEstimatorItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getEstimatorName()->getLabel(); ?></label>
            <div class="field-editable row no-gutter" data-type="estimator" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <select name="sel_estimator" class="form-control field-input input-sm hidden">
                        <?php foreach ($this->getEstimatorName()->getFieldSel() as $opt) { ?>
                            <?php
                                $selected = ($this->getEstimatorName()->getFieldVal() == $opt->value) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                <?php echo $opt->text; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val hidden"><?php echo $this->getEstimatorName()->getFieldVal(); ?></span>
                        <span class="curr-txt"><?php echo $this->getEstimatorName()->getItem(); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getJobClassificationItem
     * 
     * @return string html
     */
    protected function getJobClassificationItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getJobClassification()->getLabel();?></label>
            <p class="form-control-static field-val"><?php echo $this->getJobClassification()->getItem(); ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getStartDateItem
     * 
     * @return string html
     */
    protected function getStartDateItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getStartDate()->getLabel();?></label>
            <div class="field-editable field-calendar row no-gutter" data-type="start" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <div class="sub-field-group hidden">
                        <div class="datepicker-container field-input"></div>
                        <?php if ($this->getStartDate()->getItem() != '') { ?>
                            <div class="additional-info hidden">
                                <?php if (count($this->getStartDate()->getFieldSel()) > 0) { ?>
                                    <label>Reason</label>
                                    <select class="form-control sub-field-input" name="reason[start]" size="6" multiple="multiple">
                                        <?php foreach ($this->getStartDate()->getFieldSel() as $opt) { ?>
                                            <option value="<?php echo $opt->value; ?>"><?php echo $opt->text; ?></option>
                                        <?php } ?>
                                    </select>
                                <?php } ?>
                                <label>Details</label>
                                <textarea name="details[start]" class="form-control sub-field-input"></textarea>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val">
                            <?php if ($this->getStartDate()->getItem() != '') { ?>
                                <?php echo $this->getStartDate()->getItem(); ?>
                            <?php } else { ?>
                                <strong>Add start date</strong>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getCompletionDateItem
     * 
     * @return string html
     */
    protected function getCompletionDateItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getCompletionDate()->getLabel();?></label>
            <div class="field-editable field-calendar row no-gutter" data-type="complete" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <div class="sub-field-group hidden">
                        <div class="datepicker-container field-input"></div>
                        <?php if ($this->getCompletionDate()->getItem() != '') { ?>
                            <div class="additional-info hidden">
                                <?php if (count($this->getCompletionDate()->getFieldSel()) > 0) { ?>
                                    <label>Reason</label>
                                    <select class="form-control sub-field-input" name="reason[complete]" size="6" multiple="multiple">
                                        <?php foreach ($this->getCompletionDate()->getFieldSel() as $opt) { ?>
                                            <option value="<?php echo $opt->value; ?>"><?php echo $opt->text; ?></option>
                                        <?php } ?>
                                    </select>
                                <?php } ?>
                                <label>Details</label>
                                <textarea name="details[complete]" class="form-control sub-field-input"></textarea>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val">
                            <?php if ($this->getCompletionDate()->getItem() != '') { ?>
                                <?php echo $this->getCompletionDate()->getItem(); ?>
                            <?php } else { ?>
                                <strong>Add complete date</strong>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getEventCodeItem
     * 
     * @return string html
     */
    protected function getEventCodeItem() {
        ob_start();
        ?>
        <div class="form-group">
            <label><?php echo $this->getEventCode()->getLabel(); ?></label>
            <div class="field-editable row no-gutter" data-type="e_code" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                <div class="col-sm-2">
                    <button type="button" class="field-edit btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </div>
                <div class="col-sm-9">
                    <select name="sel_e_code" class="form-control field-input input-sm hidden">
                        <?php foreach ($this->getEventCode()->getFieldSel() as $opt) { ?>
                            <?php
                                $selected = ($this->getEventCode()->getFieldVal() == $opt->value) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                <?php echo $opt->text; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="field-btns hidden">
                        <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                        <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                    </div>
                    <div class="field-val">
                        <span class="curr-val hidden"><?php echo $this->getEventCode()->getFieldVal(); ?></span>
                        <?php if ($this->getEventCode()->getFieldVal() == 0) { ?>
                            <span class="curr-txt"><strong>??Add Event Code??</strong></span>
                        <?php } else { ?>
                            <span class="curr-txt"><?php echo $this->getEventCode()->getItem(); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * getBusinessLineItem
     * 
     * @return string html
     */
    protected function getBusinessLineItem() {
        ob_start();

        if ($this->getBusinessLine()->getItem() != '') {
            ?>
            <div class="form-group">
                <label><?php echo $this->getBusinessLine()->getLabel(); ?></label>
                <div class="field-editable row no-gutter" data-type="business_line" data-jid="<?php echo $this->getDataItem('job_details', 'job_id'); ?>">
                    <div class="col-sm-2">
                        <button type="button" class="field-edit btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </button>
                    </div>
                    <div class="col-sm-9">
                        <select name="sel_business_line" class="form-control field-input input-sm hidden">
                            <?php foreach ($this->getBusinessLine()->getFieldSel() as $opt) { ?>
                                <?php
                                    $selected = ($this->getBusinessLine()->getFieldVal() == $opt->value) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $opt->value; ?>" <?php echo $selected; ?>>
                                    <?php echo $opt->text; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="field-btns hidden">
                            <button type="button" class="btn btn-default btn-xs" data-action="save">Save</button>
                            <button type="button" class="btn btn-default btn-xs" data-action="cancel">Cancel</button>
                        </div>
                        <div class="field-val">
                            <span class="curr-val hidden"><?php echo $this->getBusinessLine()->getFieldVal(); ?></span>
                            <span class="curr-txt"><?php echo $this->getBusinessLine()->getItem(); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

}
