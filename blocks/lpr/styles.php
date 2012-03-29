/** Learner Progress Review (LPR) **/
<?php
// include the CSS from the 3rd party modules
require_once($CFG->dirroot.'/blocks/lpr/views/css/calendar.css');
?>

.block_lpr {
    text-align      : left;
    width           : 90%;
    margin          : 0px auto 0px auto;
}

.block_lpr .buttons {
    text-align      : center;
}

.block_lpr table {
    margin-bottom   : 20px;
    width           : 100%;
}

.block_lpr td,
.block_lpr th {
    border          : 1px solid #EEEEEE;
    padding         : 2px 4px 2px 4px;
    vertical-align  : top;
}

.block_lpr th {
    color           : #000000;
    font-weight     : bold;
    text-align      : center;
}

.lpr_container {
    border          : 1px solid #DDDDDD;
    margin-bottom   : 10px;
    display         : inline;
    float           : right;
    width           : 500px;
}

.block_lpr_buttons {
    text-align      : center;
    margin-bottom   : 20px;
}

.block_lpr_buttons form,
.block_lpr_buttons div {
    display         : inline;
}

.block_lpr_center {
    width           : 80%;
    margin          : 10px auto;
}

.block_lpr_center th.header {
    padding         : 4px;
    text-align      : left;
    vertical-align  : middle;
}

.block_lpr .half {
    width           : 50%;
}

.block_lpr .quater
    width           : 25%;
}

.block_lpr textarea {
    overflow-y      : scroll;
}

#optiontable {
    width           : auto;
}

.block_lpr .comments {
    border          : 0;
    width           : 100%;
    height          : 300px;
    overflow-y      : scroll;
}

.block_lpr .categorylist {
    background-color: #F5F5F5;
    border          : 1px solid #DDDDDD;
    margin          : 10px auto;
}

.block_lpr td.category {
    padding         : 10px 5px;
    font-weight     : bold;
    border          : 0;
}

.block_lpr_ilp_container {
    margin              : 0 !important;
    padding             : 0 !important;
    vertical-align      : top;
}

.block_lpr_ilp_container .fit {
    width           : 100%;
    margin          : 0;
}

#lprs .commands {
    display         : inline;
    margin-right    : 5px;
}

#ilps .commands {
    display         : inline;
    margin-right    : 5px;
}

#lprs th {
    white-space     :nowrap;
}

.block_lpr .category_browser .cat_container {
    display         : none;
}

.block_lpr .category_browser {
    width           : 30%;
    border          : 1px solid #DDDDDD;
    background-color: #F5F5F5;
    color           : #009EDB;
    float           : left;
    padding         : 5px;
}

.block_lpr .category_browser a {
    color           : #009EDB;
}

.block_lpr .category_browser ul {
    margin-left     : 10px;
    margin-bottom   : 0px;
}

.block_lpr .category_browser li {
    list-style      : none;
    clear           : both;
}

.block_lpr .category_browser .nav {
    cursor          : pointer;
}

.block_lpr .category_report {
    float           : left;
    width           : 65%;
    margin-left     : 5px;
}

.block_lpr .clearer {
    clear           : both;
}

.block_lpr .risk {
    color           : #ff0000;
}

.block_lpr .date_selector {
    margin-bottom   : 10px;
    text-align      : center;
}

.block_lpr .date_selector input[type="text"] {
    width           : 100px;
}

.lpr_progress_bar {
    width           : 200px;
    height          : 15px;
    border          : 1px solid #000000;
    background-color: #FFFFFF;
	text-align		: left !important;
}

.lpr_progress_bar .progress_avg {
    background-color: #FF6600;
    border          : 0 none;
    height          : 13px;
}

.lpr_progress_bar .attendance_avg {
    background-color: #4D89F9;
    border          : 0 none;
    height          : 13px;
}

.lpr_progress_bar .punctuality_avg {
    background-color: #80C65A;
    border          : 0 none;
    height          : 13px;
}

.block_lpr .export {
    width           : 375px;
    margin          : 10px auto;

}

.block_lpr .export select {
    width           : 260px;
    float           : right;
}

.block_lpr .export input[type="file"] {
    width           : 260px;
    float           : right;
}

.block_lpr .export input[name="folder"] {
    width           : 256px;
    float           : right;
}

.block_lpr .export span {
    float           : right;
}

.block_lpr .export .msg {
    color           : red;
    margin-bottom   : 10px;
}

.block_lpr .export fieldset {
    width           : 100%;
    padding         : 10px;
}

.block_lpr .choosemod {
	text-align: center;
}

.yui-t4 .yui-b ul li ul li {
	margin-left:10px;
}
.block_lpr label {
	/*font-size:0.95em;*/
}
table.pdf_exports {
	margin:0;
	padding:0;
	margin-top:10px;
}
table.pdf_exports td {
	border:none;
	padding:4px;
}
table.pdf_exports p {
	margin:0;
	padding:0;
}
table.pdf_exports label {

}
input#email_field, input#email_field2 {
	width:252px;
	padding:2px;
	float:right;
}
/* nkowald - 2011-06-14 - Changed styling */
#category_browser a {
    color:#414141;
    text-decoration:none;
}
#category_browser a:hover {
    text-decoration:underline;
}
#category_browser img {
    margin-right:2px;
}
#category_browser a img {
    margin-right:0;
}
#category_browser a.active {
    font-weight:bold;
}
#category_browser ul li {
    margin-bottom:5px;
}
#category_browser ul li ul {
    margin-top:3px;
}
#category_browser ul li ul li {
    margin-bottom:1px;
}
.block_lpr {
    width:100% !important;
}