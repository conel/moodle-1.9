<?php // $Id: category.php,v 1.119.2.12 2008/12/11 09:21:53 tjhunt Exp $
      // Displays the top level category or all courses
      // In editing mode, allows the admin to edit a category,
      // and rearrange courses

    require_once("../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);          // Category id
    $page = optional_param('page', 0, PARAM_INT);     // which page to show
    //$perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT); // how many per page
    $perpage = optional_param('perpage', 100, PARAM_INT); // how many per page
    $categoryedit = optional_param('categoryedit', -1, PARAM_BOOL);
    $hide = optional_param('hide', 0, PARAM_INT);
    $show = optional_param('show', 0, PARAM_INT);
    $moveup = optional_param('moveup', 0, PARAM_INT);
    $movedown = optional_param('movedown', 0, PARAM_INT);
    $moveto = optional_param('moveto', 0, PARAM_INT);
    $resort = optional_param('resort', 0, PARAM_BOOL);
	/* nkowald - 2010-02-26 - Current move up / move down methodology is flawed because if user hits back button 
	   and the move up param still exists in get parameters, the course will move up a second time. */
	$move_this = optional_param('move_this', 0, PARAM_INT);
	$move_to_pos = optional_param('move_to_pos', 0, PARAM_INT);
	
    if ($CFG->forcelogin) {
        require_login();
    }

    if (!$site = get_site()) {
        error('Site isn\'t defined!');
    }

    if (empty($id)) {
        error("Category not known!");
    }

    if (!$context = get_context_instance(CONTEXT_COURSECAT, $id)) {
        error("Category not known!");
    }

    if (!$category = get_record("course_categories", "id", $id)) {
        error("Category not known!");
    }
    if (!$category->visible) {
        require_capability('moodle/category:viewhiddencategories', $context);
    }

    if (update_category_button($category->id)) {
        if ($categoryedit !== -1) {
            $USER->categoryediting = $categoryedit;
        }
        $editingon = !empty($USER->categoryediting);
        $navbaritem = update_category_button($category->id); // Must call this again after updating the state.
    } else {
        $navbaritem = print_course_search("", true, "navbar");
        $editingon = false;
    }

    // Process any category actions.
    if (has_capability('moodle/category:manage', $context)) {
        /// Resort the category if requested
        if ($resort and confirm_sesskey()) {
            if ($courses = get_courses($category->id, "fullname ASC", 'c.id,c.fullname,c.sortorder')) {
                // move it off the range
                $count = get_record_sql('SELECT MAX(sortorder) AS max, 1 FROM ' . $CFG->prefix . 'course WHERE category=' . $category->id);
                $count = $count->max + 100;
                begin_sql();
                foreach ($courses as $course) {
                    set_field('course', 'sortorder', $count, 'id', $course->id);
                    $count++;
                }
                commit_sql();
                fix_course_sortorder($category->id);
            }
        }
    }

    if(!empty($CFG->allowcategorythemes) && isset($category->theme)) {
        // specifying theme here saves us some dbqs
        theme_setup($category->theme);
    }

/// Print headings
    $numcategories = count_records('course_categories');

    $stradministration = get_string('administration');
    $strcategories = get_string('categories');
    $strcategory = get_string('category');
    $strcourses = get_string('courses');

    $navlinks = array();
    $navlinks[] = array('name' => $strcategories, 'link' => 'index.php', 'type' => 'misc');
    $navlinks[] = array('name' => format_string($category->name), 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    if ($editingon && update_category_button()) {
        // Integrate into the admin tree only if the user can edit categories at the top level,
        // otherwise the admin block does not appear to this user, and you get an error.
        require_once($CFG->libdir.'/adminlib.php');
        admin_externalpage_setup('coursemgmt', $navbaritem, array('id' => $id,
                'page' => $page, 'perpage' => $perpage), $CFG->wwwroot . '/course/category.php');
        admin_externalpage_print_header();
		// nkowald - 2010-06-09 - Adding jquery function to move categories
        echo '
        <style type="text/css">
            #courses_sortable tbody.content tr {
                height:28px;
            }
            #courses_sortable tbody.content tr:hover {
                background-color:#FDF5CE;
            }
			#courses_sortable tbody.content tr.nohover:hover {
				background-color:#fff;
			}
            #courses_sortable tbody.content td.move:hover {
                cursor:move;
            }
        </style>';

        echo '<script type="text/javascript" src="'.$CFG->themewww .'/'. current_theme().'/jquery-ui-1.7.1.custom.min.js"></script>';
        echo "\n<script type=\"text/javascript\">
            jQuery(document).ready(function(){ 
                                       
                jQuery(function() {
                    jQuery('#courses_sortable tbody.content').sortable({ update: function() {
                            var order = jQuery(this).sortable(\"serialize\") + '&action=updateRecordsListings'; 
                            jQuery.post(\"updateDB.php\", order, function(theResponse){
                                jQuery('#ajax_message').html(theResponse).fadeIn(500).delay(700).fadeOut(500);
                            }); 															 
                        }
                    });
                    jQuery('#courses_sortable tbody.content').disableSelection();
                });

            });	
            </script>";
    } else {
        print_header("$site->shortname: $category->name", "$site->fullname: $strcourses", $navigation, '', '', true, $navbaritem);
    }

/// Print link to roles
    if (has_capability('moodle/role:assign', $context)) {
        echo '<div class="rolelink"><a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.
         $context->id.'">'.get_string('assignroles','role').'</a></div>';
    }

/// Print the category selector
    $displaylist = array();
    $notused = array();
    make_categories_list($displaylist, $notused);

    echo '<div class="categorypicker">';
    popup_form('category.php?id=', $displaylist, 'switchcategory', $category->id, '', '', '', false, 'self', $strcategories.':');
    echo '</div>';

/// Print current category description
    if (!$editingon && $category->description) {
        print_box_start();
        echo format_text($category->description); // for multilang filter
        print_box_end();
    }

/// Process any course actions.
    if ($editingon) {
    /// Move a specified course to a new category
        if (!empty($moveto) and $data = data_submitted() and confirm_sesskey()) {   // Some courses are being moved
            // user must have category update in both cats to perform this
            require_capability('moodle/category:manage', $context);
            require_capability('moodle/category:manage', get_context_instance(CONTEXT_COURSECAT, $moveto));

            if (!$destcategory = get_record('course_categories', 'id', $data->moveto)) {
                error('Error finding the category');
            }

            $courses = array();
            foreach ($data as $key => $value) {
                if (preg_match('/^c\d+$/', $key)) {
                    array_push($courses, substr($key, 1));
                }
            }
            move_courses($courses, $data->moveto);
        }

    /// Hide or show a course
        if ((!empty($hide) or !empty($show)) and confirm_sesskey()) {
            require_capability('moodle/course:visibility', $context);
            if (!empty($hide)) {
                $course = get_record('course', 'id', $hide);
                $visible = 0;
            } else {
                $course = get_record('course', 'id', $show);
                $visible = 1;
            }
            if ($course) {
                if (!set_field('course', 'visible', $visible, 'id', $course->id)) {
                    notify('Could not update that course!');
                }
            }
        }

    /// Move a course up or down

        if ((!empty($moveup) or !empty($movedown)) and confirm_sesskey()) {
            require_capability('moodle/category:manage', $context);
            $movecourse = NULL;
            $swapcourse = NULL;

            // ensure the course order has no gaps and isn't at 0
            //fix_course_sortorder($category->id);
            // we are going to need to know the range
			/* nkowald - 2009-11-11 - This is what it used to be using
            $max = get_record_sql('SELECT MAX(sortorder) AS max, 1 
                    FROM ' . $CFG->prefix . 'course WHERE category=' . $category->id);
            $max = $max->max + 100;
			*/
			
			// Moodle course ordering is baaaaad! - lets fix this.
		
			// We want to first get all courses assigned to this category and sort them by their current sort order
			if ($courses_in_category = get_records('course', 'category', $category->id, 'sortorder ASC')) {
				
				$i = 1; // counter
				foreach ($courses_in_category as $cat) {
					if ($cat->visible || has_capability('moodle/category:viewhiddencategories', $context)) {
						if(set_field('course', 'sortorder', $i, 'id', $cat->id)) {
							$i++;
						}
					}
				} // foreach
				
			} // if courses found
			
            if (!empty($moveup)) {
                $movecourse = get_record('course', 'id', $moveup);
                $swapcourse = get_record('course', 'category',  $category->id, 'sortorder', ($movecourse->sortorder - 1));
            } else {
                $movecourse = get_record('course', 'id', $movedown);
                $swapcourse = get_record('course', 'category',  $category->id, 'sortorder', ($movecourse->sortorder + 1));
            }
			
            if ($swapcourse and $movecourse) {

                // Renumber everything for robustness
                begin_sql();
				
				/*
				echo "Sort order of course to be swapped is " . $swapcourse->sortorder . "<br />";
				echo "ID of course to be moved is " . $movecourse->id . "<br />";
				echo "Sort order of course to be moved is " . $movecourse->sortorder . "<br />";
				echo "ID of course to swap is " . $swapcourse->id . "<br />";
				*/
				
                if (
				set_field('course', 'sortorder', $swapcourse->sortorder, 'id', $movecourse->id) 
				&& 
				set_field('course', 'sortorder', $movecourse->sortorder, 'id', $swapcourse->id))
				{
                    //echo 'worked';
                } else {
					//echo 'something in this query did not work';
					notify('Could not update that course!');
				}
                commit_sql();
            } 

        }
		
			// nkowald - 2010-02-26 - This is a better way to move courses up and down
        if ((!empty($move_this) && !empty($move_to_pos)) and confirm_sesskey()) {
            require_capability('moodle/category:manage', $context);
		
			// Check that course is not already in this position
			$cur_pos = 0;
			if ($current_position = get_record_select("course","id = ".$move_this."","sortorder")) {
				foreach($current_position as $pos) {
					$cur_pos = $pos;
				}
			}

			// Only change the order of the course if order is different from what it is currently
			if ($cur_pos != $move_to_pos && $cur_pos != 0) {
			
				// Get all courses assigned to this category and sort them by their current sort order
				if ($courses_in_category = get_records('course', 'category', $category->id, 'sortorder ASC')) {
					
					$num_courses = count($courses_in_category);
					
					$i = 1; // counter
					$curr_sort_order = array();
					foreach($courses_in_category as $course) {
						if ($course->visible || has_capability('moodle/category:viewhiddencategories', $context)) {		
							// If course is not the moving course add to array
							if ($course->id != $move_this) {
								$curr_sort_order[$i] = $course->id;
								$i++;
							}
						}
					} // foreach
					
					// Slice array at the move_to point, add course to move to the first part then join to get final order
					$first_half = array_slice($curr_sort_order,0,($move_to_pos - 1));
					$second_half = array_slice($curr_sort_order,($move_to_pos - 1));
					$first_half[] = $move_this;
					$new_order = array_merge($first_half,$second_half);
					
					if (!in_array($move_this,$new_order) || $move_to_pos > $num_courses) {
						echo "<b>Error:</b> Invalid course or move to position values";
					} else {
						// Update the order in the database
						begin_sql();
						$i = 1;
						foreach($new_order as $course_is) {
							set_field('course', 'sortorder', $i, 'id', $course_is);
							$i++;
						}
						commit_sql();
					}
					
				} // if courses found
				
			} // if needs to be moved

        } // if $move_this and $move_to_pos
		// nkowald
		
		
    } // End of editing stuff

    if ($editingon && has_capability('moodle/category:manage', $context)) {
        echo '<div class="buttons">';

        // Print button to update this category
        $options = array('id' => $category->id);
        print_single_button($CFG->wwwroot.'/course/editcategory.php', $options, get_string('editcategorythis'), 'get');

        // Print button for creating new categories
        $options = array('parent' => $category->id);
        print_single_button($CFG->wwwroot.'/course/editcategory.php', $options, get_string('addsubcategory'), 'get');

        echo '</div>';
    }
	
	// nkowald - 2010-11-15 - Moved course search
	print_course_search();
	echo '<br />';

/// Print out all the sub-categories
    if ($subcategories = get_records('course_categories', 'parent', $category->id, 'sortorder ASC')) {
        $firstentry = true;
        foreach ($subcategories as $subcategory) {
            if ($subcategory->visible || has_capability('moodle/category:viewhiddencategories', $context)) {
                $subcategorieswereshown = true;
                if ($firstentry) {
                    echo '<table border="0" cellspacing="2" cellpadding="4" class="generalbox boxaligncenter">';
                    echo '<tr><th scope="col">'.get_string('subcategories').'</th></tr>';
                    echo '<tr><td style="white-space: nowrap">';
                    $firstentry = false;
                }
                $catlinkcss = $subcategory->visible ? '' : 'class="dimmed" ';
                echo '<a '.$catlinkcss.' href="category.php?id='.$subcategory->id.'">'.
                     format_string($subcategory->name).'</a><br />';
            }
        }
        if (!$firstentry) {
            echo '</td></tr></table>';
            echo '<br />';
        }
    }


/// Print out all the courses
    $courses = get_courses_page($category->id, 'c.sortorder ASC',
            'c.id,c.sortorder,c.shortname,c.fullname,c.summary,c.visible,c.teacher,c.guest,c.password',
            $totalcount, $page*$perpage, $perpage);
    $numcourses = count($courses);

    if (!$courses) {
        if (empty($subcategorieswereshown)) {
            print_heading(get_string("nocoursesyet"));
        }

    } else if ($numcourses <= COURSE_MAX_SUMMARIES_PER_PAGE and !$page and !$editingon) {
        print_box_start('courseboxes');
        print_courses($category);
        print_box_end();

    } else {
        print_paging_bar($totalcount, $page, $perpage, "category.php?id=$category->id&amp;perpage=$perpage&amp;");

        $strcourses = get_string('courses');
        $strselect = get_string('select');
        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $strbackup = get_string('backup');
        $strrestore = get_string('restore');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');
        $strupdate = get_string('update');
        $strhide = get_string('hide');
        $strshow = get_string('show');
        $strsummary = get_string('summary');
        $strsettings = get_string('settings');
        $strassignteachers = get_string('assignteachers');
        $strallowguests = get_string('allowguests');
        $strrequireskey = get_string('requireskey');


		echo '<div id="ajax_message"></div>';
        echo '<form id="movecourses" action="category.php" method="post"><div>';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<table border="0" cellspacing="2" cellpadding="4" class="generalbox boxaligncenter" id="courses_sortable">';
        echo '<tbody class="content">';
        echo '<tr>';
        echo '<th class="header" scope="col">'.$strcourses.'</th>';
        if ($editingon) {
            echo '<th class="header" scope="col">'.$stredit.'</th>';
            echo '<th class="header" scope="col">'.$strselect.'</th>';
        } else {
            echo '<th class="header" scope="col">&nbsp;</th>';
        }
        echo '</tr>';


        $count = 0;
        $abletomovecourses = false;  // for now

        // Checking if we are at the first or at the last page, to allow courses to
        // be moved up and down beyond the paging border
        if ($totalcount > $perpage) {
            $atfirstpage = ($page == 0);
            if ($perpage > 0) {
                $atlastpage = (($page + 1) == ceil($totalcount / $perpage));
            } else {
                $atlastpage = true;
            }
        } else {
            $atfirstpage = true;
            $atlastpage = true;
        }

        $spacer = '<img src="'.$CFG->wwwroot.'/pix/spacer.gif" class="iconsmall" alt="" /> ';
		$i = 1;
        foreach ($courses as $acourse) {
            if (isset($acourse->context)) {
                $coursecontext = $acourse->context;
            } else {
                $coursecontext = get_context_instance(CONTEXT_COURSE, $acourse->id);
            }

            $count++;
            $up = ($count > 1 || !$atfirstpage);
            $down = ($count < $numcourses || !$atlastpage);

            $linkcss = $acourse->visible ? '' : ' class="dimmed" ';
echo '<tr class="movable" id="recordsArray_'.$acourse->id.'">';
            $editing_space = ($editingon) ? $spacer . $spacer . $spacer : '';

            echo '<td class="move"><a '.$linkcss.' href="view.php?id='.$acourse->id.'">'. format_string($acourse->fullname) .'</a>'.$editing_space.'</td>';
            if ($editingon) {
                echo '<td style="text-align:center;">';

                if (has_capability('moodle/course:update', $coursecontext)) {
                    echo '<a title="'.$strsettings.'" href="'.$CFG->wwwroot.'/course/edit.php?id='.$acourse->id.'">'.
                            '<img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall" alt="'.$stredit.'" /></a> ';
                } else {
                    echo $spacer;
                }

                // role assignment link
                if (has_capability('moodle/role:assign', $coursecontext)) {
                    echo '<a title="'.get_string('assignroles', 'role').'" href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$coursecontext->id.'">'.
                            '<img src="'.$CFG->pixpath.'/i/roles.gif" class="iconsmall" alt="'.get_string('assignroles', 'role').'" /></a> ';
                } else {
                    echo $spacer;
                }

                if (can_delete_course($acourse->id)) {
                    echo '<a title="'.$strdelete.'" href="delete.php?id='.$acourse->id.'">'.
                            '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a> ';
                } else {
                    echo $spacer;
                }

                // MDL-8885, users with no capability to view hidden courses, should not be able to lock themselves out
                if (has_capability('moodle/course:visibility', $coursecontext) && has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                    if (!empty($acourse->visible)) {
                        echo '<a title="'.$strhide.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                            '&amp;perpage='.$perpage.'&amp;hide='.$acourse->id.'&amp;sesskey='.$USER->sesskey.'">'.
                            '<img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'.$strhide.'" /></a> ';
                    } else {
                        echo '<a title="'.$strshow.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                            '&amp;perpage='.$perpage.'&amp;show='.$acourse->id.'&amp;sesskey='.$USER->sesskey.'">'.
                            '<img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'.$strshow.'" /></a> ';
                    }
                } else {
                    echo $spacer;
                }

                if (has_capability('moodle/site:backup', $coursecontext)) {
                    echo '<a title="'.$strbackup.'" href="../backup/backup.php?id='.$acourse->id.'">'.
                            '<img src="'.$CFG->pixpath.'/t/backup.gif" class="iconsmall" alt="'.$strbackup.'" /></a> ';
                } else {
                    echo $spacer;
                }

                if (has_capability('moodle/site:restore', $coursecontext)) {
                    echo '<a title="'.$strrestore.'" href="../files/index.php?id='.$acourse->id.
                         '&amp;wdir=/backupdata">'.
                         '<img src="'.$CFG->pixpath.'/t/restore.gif" class="iconsmall" alt="'.$strrestore.'" /></a> ';
                } else {
                    echo $spacer;
                }

                if (has_capability('moodle/category:manage', $context)) {
                    /*
                    if ($up) {
                        echo '<a title="'.$strmoveup.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                             '&amp;perpage='.$perpage.'&amp;moveup='.$acourse->id.'&amp;sesskey='.$USER->sesskey.'">'.
                             '<img src="'.$CFG->pixpath.'/t/up.gif" class="iconsmall" alt="'.$strmoveup.'" /></a> ';
                    } else {
                        echo $spacer;
                    }

                    if ($down) {
                        echo '<a title="'.$strmovedown.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                             '&amp;perpage='.$perpage.'&amp;movedown='.$acourse->id.'&amp;sesskey='.$USER->sesskey.'">'.
                             '<img src="'.$CFG->pixpath.'/t/down.gif" class="iconsmall" alt="'.$strmovedown.'" /></a> ';
                    } else {
                        echo $spacer;
                    }
					*/
					// nkowald - 2010-03-01 - Improving course ordering
					/*
					if ($up) {
						$move_to_up = $i - 1;
                        echo '<a title="'.$strmoveup.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                             '&amp;perpage='.$perpage.'&amp;move_this='.$acourse->id.'&amp;move_to_pos='.$move_to_up.'&amp;sesskey='.$USER->sesskey.'">'.
                             '<img src="'.$CFG->pixpath.'/t/up.gif" class="iconsmall" alt="'.$strmoveup.'" /></a> ';
                    } else {
                        echo $spacer;
                    }

                    if ($down) {
						$move_to_down = $i + 1;
                        echo '<a title="'.$strmovedown.'" href="category.php?id='.$category->id.'&amp;page='.$page.
                             '&amp;perpage='.$perpage.'&amp;move_this='.$acourse->id.'&amp;move_to_pos='.$move_to_down.'&amp;sesskey='.$USER->sesskey.'">'.
                             '<img src="'.$CFG->pixpath.'/t/down.gif" class="iconsmall" alt="'.$strmovedown.'" /></a> ';
                    } else {
                        echo $spacer;
                    }
					*/
                    $abletomovecourses = true;
					
                } else {
                    echo $spacer, $spacer;
                }

                echo '</td>';
                echo '<td style="text-align:center;">';
                echo '<input type="checkbox" name="c'.$acourse->id.'" />';
                echo '</td>';
            } else {
                echo '<td align="right">';
                if (!empty($acourse->guest)) {
                    echo '<a href="view.php?id='.$acourse->id.'"><img title="'.
                         $strallowguests.'" class="icon" src="'.
                         $CFG->pixpath.'/i/guest.gif" alt="'.$strallowguests.'" /></a>';
                }
                if (!empty($acourse->password)) {
                    echo '<a href="view.php?id='.$acourse->id.'"><img title="'.
                         $strrequireskey.'" class="icon" src="'.
                         $CFG->pixpath.'/i/key.gif" alt="'.$strrequireskey.'" /></a>';
                }
                if (!empty($acourse->summary)) {
                    link_to_popup_window ("/course/info.php?id=$acourse->id", "courseinfo",
                                          '<img alt="'.get_string('info').'" class="icon" src="'.$CFG->pixpath.'/i/info.gif" />',
                                           400, 500, $strsummary);
                }
                echo "</td>";
            }
            echo "</tr>";
			$i++; // increase counter
        } // foreach course

        if ($abletomovecourses) {
            $movetocategories = array();
            $notused = array();
            make_categories_list($movetocategories, $notused, 'moodle/category:manage');
            $movetocategories[$category->id] = get_string('moveselectedcoursesto');
            echo '<tr class="nohover"><td colspan="3" align="right">';
            choose_from_menu($movetocategories, 'moveto', $category->id, '', "javascript:submitFormById('movecourses')");
            echo '<input type="hidden" name="id" value="'.$category->id.'" />';
            echo '</td></tr>';
        }

        echo '</table>';
        echo '</div></form>';
        echo '<br />';
    }

    echo '<div class="buttons">';
    if (has_capability('moodle/category:manage', $context) and $numcourses > 1) {
    /// Print button to re-sort courses by name
	/* - uncommented for now as it resorts courses category wide
        unset($options);
        $options['id'] = $category->id;
        $options['resort'] = 'name';
        $options['sesskey'] = $USER->sesskey;
        print_single_button('category.php', $options, get_string('resortcoursesbyname'), 'get');
	*/
    }

    if (has_capability('moodle/course:create', $context)) {
    /// Print button to create a new course
        unset($options);
        $options['category'] = $category->id;
        print_single_button('edit.php', $options, get_string('addnewcourse'), 'get');
    }

    if (!empty($CFG->enablecourserequests) && $category->id == $CFG->enablecourserequests) {
        print_course_request_buttons(get_context_instance(CONTEXT_SYSTEM));
    }
    echo '</div>';

	// nkowald - 2010-11-15 - Moved course search ABOVE categories/course list
    //print_course_search();

    print_footer();

?>
