<?php

require_once('config.php');
require_login(); 

if (!isadmin()) {
    error('Only the administrator can access this page!', $CFG->wwwroot);
}

/* ---------------------- Settings ------------------------ */
    $search    = 'domain.com/VLE/';
    $replace   = 'vle.domain.com';

    $case_sensitive = false; // false = Match 'vle' and 'VLE'
/* -------------------------------------------------------- */


$records1 = get_records_select('block_instance', 'configdata != "" AND configdata != "Tjs="');
$records2 = get_records_select('block_pinned', 'configdata != "" AND configdata != "Tjs="');
$records  = array_merge($records1, $records2);

if (count($records > 0)) {

    echo "<h2>Replacing '<span style=\"color:red;\">$search</span>' with '<span style=\"color:green;\">$replace</span>' inside block content.</h2>";
    echo "<h3>".count($records)." non-empty block instances found</h3>";
    echo '<pre>';

    $found_count = 0;
    $c = 1;
    foreach ($records as $record) {
        
        $result = "<p><strong>Block $c</strong> - ";

        $configdata = unserialize(base64_decode($record->configdata));
        if ($configdata->text !== NULL) {
        
            // Allows URL replacement inside links
            $html = htmlspecialchars($configdata->text);
            $string_found = ($case_sensitive) ? strpos($html, $search) : stripos($html, $search);

            if ($string_found !== false) {
            
                $configdata->text = ($case_sensitive) ?
                    htmlspecialchars_decode(str_replace($search, $replace, $html)) : 
                    htmlspecialchars_decode(str_ireplace($search, $replace, $html));

                $record->configdata = base64_encode(serialize($configdata));
                
                $table = (isset($record->page_id)) ? 'block_instance' : 'block_pinned';
                if (update_record($table, $record)) {
                    $result .= '<strong style="color:green;">Found and replaced!</strong>';
                    $found_count++;
                } else {
                    $result .= 'FAILed to update block_instance :*(';
                }
                
            } else {
                $result .= 'Search string not found';
            }
            
        } else {
            $result .= 'No content';

        }
        echo $result . '</p>';
        $c++;
        
    }

    echo '</pre>';
    echo "<h3>$found_count blocks updated to use the string '$replace' instead of '$search'.</h3>";

} else {
    print_heading('FAIL!');
    echo '<p>No block instances found.</p>';
}

?>
