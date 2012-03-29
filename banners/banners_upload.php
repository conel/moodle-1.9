<?php

    require_once('../config.php');
    require_login(); 

    if (!isadmin()) {
        error("Only the administrator can access this page!", $CFG->wwwroot);
    }

	$action = (isset($_REQUEST['action']) && $_REQUEST['action'] != '') ? $_REQUEST['action'] : '' ;
	$banner_pos = (isset($_REQUEST['pos']) && $_REQUEST['pos'] != '') ? $_REQUEST['pos'] : '' ;
	$role = optional_param('role', 1, PARAM_INT);

	// This should be run after every 'delete'.
	function updateOrder() {
	
		global $role;
		$query = "SELECT * FROM mdl_banners WHERE role = $role ORDER BY position ASC";
        if ($results = get_records_sql($query)) {
			$pos = 1;
            foreach ($results as $res) {
                $update_query = sprintf("UPDATE mdl_banners SET position = %d WHERE id = %d", 
                    $pos,
                    $res->id    
                );
				if (!$updated = execute_sql($update_query)) {
					echo 'Banner order update FAILED!';
					return FALSE;
					exit;
				}
				$pos++;
			}
			return TRUE;
		} else {
			// No banners exist, let's return true
			return TRUE;
		}
	}

	function move($banner_pos='', $swap_pos='') {
	
		global $role;
		
		if ($banner_pos != '' && $swap_pos != '') {
			// get id numbers of banners which need to be swapped
			$order_pos = ($swap_pos > $banner_pos) ? 'ASC' : 'DESC';
			$query = "SELECT id FROM mdl_banners WHERE position IN ($swap_pos, $banner_pos) AND role = $role ORDER BY position $order_pos";

			if ($results = get_records_sql($query)) {
                if (count($results) > 0) {
                    foreach ($results as $row) {
                        $ids[] = $row->id;
                    }
                }
			}
			$i = 0;
			$error = FALSE;
			// what is the current date and time?
			$date_modified = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format

			foreach ($ids as $id) {
				if ($i == 0) {
					$query = "UPDATE mdl_banners SET position = '$swap_pos', date_modified = '$date_modified' WHERE id = $id";
					$result = execute_sql($query);
					if (!$result) { $error = TRUE; $error_msg = "Could not update banner position: $query"; }
				} else if ($i == 1) {
					$query = "UPDATE mdl_banners SET position = '$banner_pos', date_modified = '$date_modified' WHERE id = $id";
					$result = execute_sql($query);
					if (!$result) { $error = TRUE; $error_msg = "Could not update banner position: $query"; }
				}
				$i++;
			}
			if ($error === FALSE) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}
	

	switch ($action) {
		case 'upload':

			// Check that we have a file
			if((!empty($_FILES["banner_img"])) && ($_FILES['banner_img']['error'] == 0)) {

			  // Check if the file is JPEG image or GIF and its size is less than 500Kb
			  $filename    = basename($_FILES['banner_img']['name']);
			  $valid_exts  = array('jpg', 'jpeg', 'png', 'gif');
			  $valid_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/pjpeg');
			  $ext         = substr($filename, strrpos($filename, '.') + 1);

			  if ((in_array($ext, $valid_exts)) && (in_array($_FILES["banner_img"]["type"], $valid_mimes)) && ($_FILES["banner_img"]["size"] < 500000)) {
				  //Determine the path to which we want to save this file
				  $newname = $CFG->dirroot .'\banners\\'.$filename;
				  //Check if the file with the same name is already exists on the server
				  if (!file_exists($newname)) {
					//Attempt to move the uploaded file to it's new place
					if ((move_uploaded_file($_FILES['banner_img']['tmp_name'], $newname))) {
						// echo "It's done! The file has been saved as: ".$newname;
						// Banner successfully updated!
						
						// Only validate URL if banner link given
						if ($_POST['banner_link'] != '') {
							if (filter_var($_POST['banner_link'], FILTER_VALIDATE_URL)) {
								$banner_link = filter_var($_POST['banner_link'], FILTER_VALIDATE_URL);
							} else {
								echo "Error: Invalid URL";
								exit;
							}
						}
						if (is_numeric($_POST['position'])) {
							$position = $_POST['position'];
						} else {
							echo "Error: Position must be numeric";
							exit;
						}
						
						$active = 1;
						$banner_url = '/banners/'.$filename;
						$author = $USER->id;
						// what is the current date and time?
						$date_created = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format

						// nkowald - 2012-02-27 - Added Role from hidden POST field
						$role = (isset($_POST['role']) && ($_POST['role'] == 1 || $_POST['role'] == 2)) ? $_POST['role'] : 1;

						$query = sprintf("INSERT INTO mdl_banners 
							(position, link, img_url, active, author, role, date_created) 
							VALUES 
							('%d', '%s', '%s', '%1d', '%s', '%1d', '%s')", 
							$position, $banner_link, $banner_url, $active, $author, $role, $date_created
						);
							
						$result = execute_sql($query);
						if ($result) {
                            updateOrder();
							$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
							header("Location: $redirect");
							exit;
						}

					} else {
					   echo "Error: A problem occurred during file upload!";
					}
				  } else {
					 echo "Error: File ".$_FILES["banner_img"]["name"]." already exists";
				  }
			  } else {
				 echo "Error: Only .jpg, .jpeg, .png, .gif images under 500Kb are accepted for upload";
			  }

			} else {
			 echo "Error: No file uploaded";
			}
				
			break;

		case 'moveup':
			$move_to = $banner_pos - 1;
			$result = move($banner_pos, $move_to);
			if ($result) {
				$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
				header("Location: $redirect");
				exit;
			} else {
				echo 'Error moving banner up';
				exit;
			}
			break;

		case 'movedown':
			$move_to = $banner_pos + 1;
			$result = move($banner_pos, $move_to);
			if ($result) {
				$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
				header("Location: $redirect");
				exit;
			} else {
				echo 'Error moving banner down';
				exit;
			}
			break;

		case 'delete':
			if ($banner_pos != '') {
				// get image filename and idnumber of banner to delete
				//$query = "SELECT id, img_url FROM mdl_banners WHERE position = $banner_pos";
				$banner_id = '';
				$img_url = '';
                if ($ban_found = get_record('banners', 'position', $banner_pos, 'role', $role)) {
					$banner_id = $ban_found->id;	
					$img_url = $ban_found->img_url;	
				}

				if ($banner_id != '' && $img_url != '') {

					// Delete the banner from the table
					$query = "DELETE FROM mdl_banners WHERE id = $banner_id";
					$result = execute_sql($query);

					if ($result) {
						// Delete the file from banners directory - to save space and prevent 'duplicate' image errors
                        // TODO - not sure WHAT should be here
						$newname = str_replace('/banners/', '', $img_url);
						$filepath = $CFG->dirroot .'\banners\\' . $newname;
						//Check if the file with the same name is already exists on the server
						if (file_exists($filepath)) {
							$delete = unlink($filepath);
							if ($delete) {
								// Before redirect, update order of banners
								$order_update = updateOrder();
								if (!$order_update) {
									echo 'Could not update banner order!';
									exit;
								}
								// Successfully deleted! - return to banners page
								$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
								header("Location: $redirect");
								exit;
							} else {
								// Error deleting, not sure what to do here, how about NOTHING!
							}
						}
					} else {
						echo "Could not delete $banner_id";
						exit;
					}

				} else {
					echo 'Banner not found!';
					exit;
				}
			}
			break;

		case 'disable':
			if ($banner_pos != '') {
				$date_modified = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format
				$query = "UPDATE mdl_banners SET active=0, position=100, date_modified = '$date_modified' WHERE position = $banner_pos AND role = $role";
				if ($result = execute_sql($query)) {
					$order_update = updateOrder();
					if (!$order_update) {
						echo 'Could not update banner order!';
						exit;
					}
					// Successfully disabled and re-ordered, redirect to home
					$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
					header("Location: $redirect");
					exit;
				} else {
					echo "Could not disable banner $banner_pos";
					exit;
				}
			}
			break;

		case 'enable':
			if ($banner_pos != '') {
				$date_modified = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format
				$query = "UPDATE mdl_banners SET active=1, position=100, date_modified = '$date_modified' WHERE position = $banner_pos AND role = $role";

				if ($result = execute_sql($query)) {
					$order_update = updateOrder();
					if (!$order_update) {
						echo 'Could not update banner order!';
						exit;
					}
					// Successfully disabled and re-ordered, redirect to home
					$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
					header("Location: $redirect");
					exit;
				} else {
					echo "Could not enable banner $banner_pos";
					exit;
				}
			}
			break;

		case 'update':
			$id = (isset($_REQUEST['id']) && $_REQUEST['id'] != '') ? $_REQUEST['id'] : '';
			$link = (isset($_REQUEST['banner_link']) && $_REQUEST['banner_link'] != '') ? $_REQUEST['banner_link'] : '';
			
			// Validate link, just in case
			if ($link != '') {
				if (!filter_var($link, FILTER_VALIDATE_URL)) {
					echo "Error: Invalid URL";
					exit;
				}
			}
			
			$new_banner = ((!empty($_FILES["new_banner_img"])) && ($_FILES['new_banner_img']['error'] == 0)) ? $_FILES['new_banner_img'] : '';
			
			// If updating banner, delete old banner and then upload new banner
			if ($new_banner != '') {
				// delete old banner	
				$query = "SELECT img_url FROM mdl_banners WHERE id = $id";
                $old_banner = get_record('banners', 'id', $id);
				if ($old_banner) {
                    $img_url = $old_banner->img_url;
					// Now we have image url: delete it!
					$newname = str_replace('/banners/', '', $img_url);
					$filepath = $CFG->dirroot .'\banners\\' . $newname;
					//Check if the file with the same name is already exists on the server
					if (file_exists($filepath)) {
						$delete = unlink($filepath);
						if ($delete) {
						  // Deleted successfully, now upload new banner
						  //Check if the file is JPEG image or GIF and its size is less than 500Kb
						  $filename = basename($_FILES['new_banner_img']['name']);
						  $valid_exts = array('jpg', 'jpeg', 'png', 'gif');
						  $valid_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/pjpeg');
						  $ext = substr($filename, strrpos($filename, '.') + 1);

						  if ((in_array($ext, $valid_exts)) && (in_array($_FILES["new_banner_img"]["type"], $valid_mimes)) && ($_FILES["new_banner_img"]["size"] < 500000)) {
							  //Determine the path to which we want to save this file
							  $newname = $CFG->dirroot.'\banners\\'.$filename;
							  //Check if the file with the same name is already exists on the server
							  if (!file_exists($newname)) {
								//Attempt to move the uploaded file to it's new place
								if ((move_uploaded_file($_FILES['new_banner_img']['tmp_name'], $newname))) {
									// echo "It's done! The file has been saved as: ".$newname;
									// Banner successfully updated!
									// Now finally, update the database record with new details
									$date_modified = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format
									$query = "UPDATE mdl_banners SET link='$link', img_url='/banners/$filename', date_modified='$date_modified' WHERE id = $id";
									if ($success = execute_sql($query)) {
										// Woo hoo! everything works : redirect to home
										$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
										header("Location: $redirect");
										exit;
									} else {
										echo 'Error: Banner update failed!';
									}
								} else {
									echo 'Error: Error uploading new banner image';
								}
							  } else {
								 echo "Error: File ".$_FILES["new_banner_img"]["name"]." already exists";
							  }

						  } else {
							echo "Error: Only .jpg, .png, .gif images under 500Kb are accepted for upload";
						  }
						}
					}
				}
			} else {
				// NOT updating banner so just update link and date modified
				$date_modified = date('Y-m-d H:i:s'); // "2009-06-21 14:34:04": MySQL timestamp format
				$query = "UPDATE mdl_banners SET link='$link', date_modified='$date_modified' WHERE id = $id";
				if ($success = execute_sql($query)) {
					// Woo hoo! everything works : redirect to home
					$redirect = $CFG->wwwroot . "/banners/index.php?role=$role";
					header("Location: $redirect");
					exit;
				} else {
					echo 'Error: Could not update banner';
				}
			}
			break;

	}

?>
