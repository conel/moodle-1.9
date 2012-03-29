<?PHP 

/*
 * @copyright &copy; 2007 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 1.0
 */

    require_once('../../../../config.php');
 
    global $USER, $CFG;

/// Print headers
    print_header("ILP: Key to Scoring");
?>
			
<div class="generalbox" id="ilp-attendance-overview">
<h2>Learner Tracking Scoring Explained</h2>

<table border="1" class="generalbox" style="text-align: left;">
  <tr>
    <th colspan="2" scope="row">Attendance &amp; Punctuality</th>
    <th scope="col">Attainment/Learning</th>
  </tr>
  <tr>
    <td>10 = 100</th>
    <td>(Outstanding)</td>
    <td>10 = All Course/class work, Assignment or other Practical work indicate<br>
      that they are acquiring the skills and knowledge to enable them to
    achieve above their Target.</td>
  </tr>
  <tr>
    <td>9 = 98-99</th>
    <td>(Excellent)</td>
    <td>8 = Course/class work, Assignment and/or other Practical work which indicates that they are
    acquiring the skills and knowledge to enable them to achieve their Target.</td>
  </tr>
  <tr>
    <td>8 = 96-97</th>
    <td>(Very good)</td>
    <td>6 = Assignment and/or other course/class work which indicates that they
      are acquiring the skills and knowledge to enable them to achieve a safe
    pass on the course.</td>
  </tr>
  <tr>
    <td>7 = 94-95</th>
    <td>(Good)</td>
    <td>4 = Assignment and/or other course/class work which indicates that they have a skill gap
    which may prevent them from succeeding on the course, without special/additional support.</td>
  </tr>
  <tr>
    <td>6 = 92-93</th>
    <td>(Quite good)</td>
    <td>2 = Assignment and/or other course work which clearly indicates that they
    are on a course which is inappropriate to their level of ability.</td>
  </tr>
  <tr>
    <td>5 = 90-91</th>
    <td>(O.K.)</td>
    <th scope="col">Employment Skills</th>
  </tr>
  <tr>
    <td>4 = 80-89</th>
    <td>(Weak)</td>
    <td>10 =Positive, Mature, diligent and reliable. Takes full responsibility for his/her own
      learning development. Highly capable of solving problems independently. Has a professional
    approach to working within a team or on own. Always completes work on time</td>
  </tr>
  <tr>
    <td>3 = 70-79</th>
    <td>(Inadequate)</td>
    <td>8 = Mature, diligent and reliable. Regularly takes responsibility for own learning.
    Solves problems through both independent and teamwork. Always completes work on time</td>
  </tr>
  <tr>
    <td>2 = 60-69</th>
    <td>(Poor)</td>
    <td>6 = Hard working with appropriate behavior at all times. Usually completes
      work on time but requires some supervision to ensure tasks are completed. Works fairly well
    in a team.</td>
  </tr>
  <tr>
    <td>1 = 60 or less</th>
    <td>(Very poor)</td>
    <td>4 = Usually well behaved. Frequently misses deadlines. Unsupervised tasks are
    frequently not completed. Contributions to team activities are inadequate.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>2 = Inappropriate behavior is common. Little learning is evident in any
    context. Always misses deadlines or has work outstanding.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <th scope="col">Functional skills (i.e. Literacy, Numercy &amp; ICT)</strong></th>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>10 = Demonstrates fluency in these skills and works independently. And
      displays outstanding competencies in functional skills. Takes full responsibility
    for own learning.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>8 = Regularly applies functional skills to course work. Usually takes responsibility for own
    learning.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>6 = Requires some supervision to complete tasks involving functional
    skills. Has limited independent approach to work.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>4 = Unsupervised functional skills tasks are frequently not completed.</td>
  </tr>
  <tr>
    <td>&nbsp;</th>
    <td>&nbsp;</td>
    <td>2 = Little learning evident of functional skills in any context.</td>
  </tr>
</table>