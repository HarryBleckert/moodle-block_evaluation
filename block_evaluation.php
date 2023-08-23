<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Evaluation block.
 *
 * @package    block_evaluation
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/evaluation/lib.php');

class block_evaluation extends block_list {

    function init() {
		$icon = '<img src="/mod/evaluation/pix/icon120.png" height="30">';
        $this->title = $icon. "&nbsp;".get_string('evaluation', 'block_evaluation');
    }

    function applicable_formats() {
        return array('site' => true, 'course' => true);
    }

    function get_content() 
	{	global $CFG, $DB, $OUTPUT, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $courseid = $this->page->course->id;
		
        if ($courseid < 1) 
		{	$courseid = SITEID; }
        if (empty($this->instance->pageid)) 
		{	$this->instance->pageid = SITEID; }
		
		$evaluations = array();
		$allowedURLS = array('/course/view.php','/user/view.php','/user/index.php'); 
		if ( $courseid != SITEID AND in_array( $_SERVER['PHP_SELF'], $allowedURLS ) )
		{	$evaluations = evaluation_get_evaluations_from_sitecourse_map($courseid);
			//{	$evaluations = $DB->get_records('evaluation', array('course' => $courseid), '*') ; }
		}
		//$this->content->items[] = "LVE $courseid: ".nl2br(var_export($evaluations,true));
		//return $this->content;
		foreach ($evaluations as $evaluation) 
		{	list( $show, $reminder) = evaluation_filter_Evaluation($courseid, $evaluation, $USER);
			if ( !$show )
			{	continue; }			
			$is_open = evaluation_is_open($evaluation);
			$showAnalysis = stristr($reminder, get_string("analysis","evaluation"));
			if ( $showAnalysis AND ( evaluation_countCourseEvaluations( $evaluation, $courseid ) >= evaluation_min_results($evaluation) ) ) //OR !$is_open ) )
			{	$baseurl = new moodle_url('/mod/evaluation/analysis_course.php'); }
			else
			{	$baseurl = new moodle_url('/mod/evaluation/view.php'); }
			$url = new moodle_url($baseurl);
			$url->params(array('id'=>$evaluation->cmid));
			$url->params(array('courseid'=>$courseid));
			
			if ( !isset( $_SESSION["allteachers"][$courseid] ) OR empty($_SESSION["allteachers"][$courseid]) )
			{	evaluation_get_course_teachers( $courseid); }
			
			// redirect to reminder page if activated! (after 17:00, on Saturdays and on Sundays)
			if ( !isset($_SESSION["Evaluation_now"]) AND $is_open AND $reminder AND ( idate("H")>17 OR idate("w")==6 OR idate("w")==0)  
				AND ( $showAnalysis OR (isset( $_SESSION["allteachers"][$courseid][$USER->id] )
					?false :$courseid != SITEID AND !evaluation_has_user_participated($evaluation, $USER->id, $courseid ) ))
			)
			{	$_SESSION["Evaluation_now"] = 1;
				redirect(new moodle_url($url)); 
			}
			
			
			// add treacherid to params if required		
			if ( isset( $_SESSION["allteachers"][$courseid][$USER->id] ) )
			{	$url->params( array('teacherid' => $USER->id ) ); }				
			$this->content->items[] = '<a href="'.$url->out().'">'.$reminder." <b>$evaluation->name</b></a><br>\n";
		}
        return $this->content;
    }
}
