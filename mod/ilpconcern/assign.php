<?php 

    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->libdir.'/adminlib.php');

    define("MAX_USERS_PER_PAGE", 5000);
    define("MAX_USERS_TO_LIST_PER_ROLE", 10);

    //$contextid      = required_param('contextid',PARAM_INT); // context id
    $roleid         = 3; // required role id
    $searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string    
    $previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);

    $userid         = optional_param('userid', 0, PARAM_INT); // needed for user tabs
    $courseid       = optional_param('courseid', 0, PARAM_INT); // needed for user tabs

    $errors = array();

    $previoussearch = ($searchtext != '') or ($previoussearch) ? 1:0;

    require_login();

    require_capability('moodle/role:assign');

	// Get some language strings
    $strpotentialusers = get_string('potentialusers', 'role');
    $strexistingusers = get_string('existingusers', 'role');
    $straction = get_string('assignroles', 'role');
    $strroletoassign = get_string('roletoassign', 'role');
    $strsearch = get_string('search');
    $strshowall = get_string('showall');
    $strparticipants = get_string('participants');
    $strsearchresults = get_string('searchresults');
    $unlimitedperiod = get_string('unlimited');

	// prints a form to swap roles
    if ($roleid) {

        $select  = "u.username <> 'guest' AND u.deleted = 0 AND u.confirmed = 1";
        $usercount = count_records_select('user', $select);
        $searchtext = trim($searchtext);

        if ($searchtext !== '') {   // Search for a subset of remaining users
            $LIKE      = sql_ilike();
            $FULLNAME  = sql_fullname();

            $selectsql = " AND ($FULLNAME $LIKE '%$searchtext%' OR u.email $LIKE '%$searchtext%') ";
            $select  .= $selectsql;
        } else {
            $selectsql = "";
        }

		$sql = 'SELECT u.id, u.firstname, u.lastname, u.email
				FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'role_assignments r
			    WHERE '.$select.' AND u.id=r.userid AND r.roleid=3 
			    ORDER BY u.lastname ASC, u.firstname ASC';

		$availableusers = get_recordset_sql($sql);

		$usercount = $availableusers->_numOfRows;         

        print_simple_box_start('center');
        include('assign.html');
        print_simple_box_end();
    }
?>
