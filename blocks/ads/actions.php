<?php
include_once('../../config.php');
// Check if any actions exist
$action = optional_param('action', '', PARAM_RAW);
$id = optional_param('id', 0, PARAM_INT);
$instanceid = optional_param('instanceid', 0, PARAM_INT);

// TODO - Make sure it can't be accessed directly, somehow

if (isset($_POST) && $action == 'insert' && $instanceid != 0) {
	// Get the position
	$position = 0;
	$query = "SELECT * FROM mdl_block_ads WHERE instanceid = $instanceid ORDER BY position DESC LIMIT 1";
	if ($data = get_records_sql($query)) {
		foreach($data as $datum) {
			$position = $datum->position;
		}
		++$position; // Add one to existing
	} else {
		// No records exist yet, set position to be 1
		$position = 1;
	}
	
	// insert the record
	$advert = new Object();
	$advert->id = 0; // auto-inc
	$advert->position = $position;
	$advert->image = $_POST['image'];
	$advert->link = $_POST['link'];
	$advert->title = $_POST['title'];
	$advert->instanceid = $_POST['instanceid'];
	$advert->date_created = time();
	$advert->date_modified = '';
	
	// Insert record
	if (insert_record('block_ads', $advert)) {
		echo 'success!';
	} else {
		echo 'fail!';
	}
} else if (isset($_POST) && $action == 'update' && $id != 0) {
	
	// get current ad data as object
	$advert = get_record('block_ads', 'id', $id);
	
	// update record from POST variables
	$advert->image = $_POST['image'];
	$advert->link = $_POST['link'];
	$advert->title = $_POST['title'];
	$advert->date_modified = time();
	
	// Update record
	if (update_record('block_ads', $advert)) {
		echo 'success!';
	} else {
		echo 'fail!';
	}
} else if (isset($_POST) && $action == 'delete' && $id != 0) {
	// Delete record
	delete_records('block_ads', 'id', $id);
	
	// Update position
	if ($adverts = get_records('block_ads')) {
		$i = 1;
		foreach ($adverts as $ad) {
			$ad->position = $i;
			update_record('block_ads', $ad);
			$i++;
		}
		echo 'success!';
	}
	
} else if ($action == 'updateorder') {
	
	$updateRecordsArray = $_POST['recordsArray'];

	$listingCounter = 1;
	foreach ($updateRecordsArray as $id) {
		if ($curr_ad = get_record('block_ads', 'id', $id)) {
			// Update current adverts position
			$curr_ad->position = $listingCounter;
			update_record('block_ads', $curr_ad);
		}
		$listingCounter++;
	}

	echo '<p class="ad_order_updated">Order saved!</p>';
}
?>