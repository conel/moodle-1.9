function setcourseitemfilter(item, item_typ) {
	document.report.courseitemfilter.value = item;
	document.report.courseitemfiltertyp.value = item_typ;
	document.report.submit();
}

// nkowald - 2009-12-04
// This function is required to reset and hide filter selections so that accurate
// search results appear for the chosen filter
function showSelection(element) {

	var school_holder = document.getElementById("school_holder");
	var course_holder = document.getElementById("course_holder");
	var directorates_holder = document.getElementById("directorates_holder");
	var levels_holder = document.getElementById("levels_holder");
	var curric_holder = document.getElementById("curriculum_area_holder");
	var menucoursefilter = document.getElementById("menucoursefilter");
	var menudirectorates = document.getElementById("menudirectorates");
	var menuschools = document.getElementById("menuschools");
	var menulevels = document.getElementById("menulevels")
	var menucurric = document.getElementById("menucurriculum_areas");

	if (element.id == "filter_school") {
		
		if (school_holder) { school_holder.style.display="block"; }
		if (course_holder) { course_holder.style.display="none"; }
		if (directorates_holder) { directorates_holder.style.display="none"; }
		if (levels_holder) { levels_holder.style.display="none"; }
		if (curric_holder) { curric_holder.style.display="none"; }
		if (menulevels) { menulevels.options[0].selected="false"; }
		if (menucoursefilter) { menucoursefilter.options[0].selected="false"; }
		if (menudirectorates) { menudirectorates.options[0].selected="false"; }
		if (menucurric) { menucurric.options[0].selected="false"; }
	}

	if (element.id == "filter_course") {
		
		// If filter by course box not on page, add text saying loading then redirect page.
		if (!course_holder) {
			var load_msg_box = document.getElementById("loading_msg");
			load_msg_box.innerHTML = "Loading all courses...<br /><br />";
		}
		
		if (school_holder) { school_holder.style.display="none"; }
		if (course_holder) { course_holder.style.display="block"; } 
		if (directorates_holder) { directorates_holder.style.display="none"; }
		if (levels_holder) { levels_holder.style.display="none"; }
		if (curric_holder) { curric_holder.style.display="none"; }
		if (menulevels) { menulevels.options[0].selected="false"; }
		if (menucoursefilter) { menucoursefilter.options[0].selected="false"; }
		if (menudirectorates) { menudirectorates.options[0].selected="false"; }
		if (menuschools) { menuschools.options[0].selected="false"; }
		if (menucurric) { menucurric.options[0].selected="false"; }

		if (!course_holder) {
			element.form.submit();
		}
		
	}

	if (element.id == "filter_directorate") {
		if (school_holder) { school_holder.style.display="none"; }
		if (course_holder) { course_holder.style.display="none"; }
		if (directorates_holder) { directorates_holder.style.display="block"; }
		if (levels_holder) { levels_holder.style.display="none"; }
		if (curric_holder) { curric_holder.style.display="none"; }
		if (menulevels) { menulevels.options[0].selected="false"; }
		if (menucoursefilter) { menucoursefilter.options[0].selected="false"; }
		if (menudirectorates) { menudirectorates.options[0].selected="false"; }
		if (menuschools) { menuschools.options[0].selected="false"; }
		if (menucurric) { menucurric.options[0].selected="false"; }
	}

	if (element.id == "filter_level") {
		if (school_holder) { school_holder.style.display="none"; }
		if (course_holder) { course_holder.style.display="none"; }
		if (directorates_holder) { directorates_holder.style.display="none"; }
		if (levels_holder) { levels_holder.style.display="block"; }
		if (curric_holder) { curric_holder.style.display="none"; }
		if (menucoursefilter) { menucoursefilter.options[0].selected="false"; }
		if (menudirectorates) { menudirectorates.options[0].selected="false"; }
		if (menuschools) { menuschools.options[0].selected="false"; }
		if (menucurric) { menucurric.options[0].selected="false"; }
	}

	if (element.id == "filter_curriculum_area") {
		if (school_holder) { school_holder.style.display="none"; }
		if (course_holder) { course_holder.style.display="none"; }
		if (directorates_holder) { directorates_holder.style.display="none"; }
		if (levels_holder) { levels_holder.style.display="none"; }
		if (menulevels) { menulevels.options[0].selected="false"; }
		if (curric_holder) { curric_holder.style.display="block"; }
		if (menucoursefilter) { menucoursefilter.options[0].selected="false"; }
		if (menudirectorates) { menudirectorates.options[0].selected="false"; }
		if (menuschools) { menuschools.options[0].selected="false"; }
	}

}
