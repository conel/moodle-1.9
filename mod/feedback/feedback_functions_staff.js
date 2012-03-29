function setcourseitemfilter(item, item_typ) {
	document.report.courseitemfilter.value = item;
	document.report.courseitemfiltertyp.value = item_typ;
	document.report.submit();
}

// nkowald - 2009-12-04
// This function is required to reset and hide filter selections so that accurate
// search results appear for the chosen filter
function showSelection(element) {

	var menucoursefilter = document.getElementById("menucoursefilter");
	var menudirectorates = document.getElementById("menudirectorates");

}
