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
 * Language strings for LiveSonner module
 *
 * @package    mod_livesonner
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LiveSonner';
$string['modulename'] = 'LiveSonner';
$string['modulenameplural'] = 'LiveSonner live classes';
$string['pluginadministration'] = 'LiveSonner administration';
$string['modulename_help'] = 'Create live classes integrated with Google Meet with attendance tracking and recording availability.';
$string['name'] = 'Class title';
$string['timestart'] = 'Start date and time';
$string['duration'] = 'Estimated duration (minutes)';
$string['meeturl'] = 'Google Meet link';
$string['teacher'] = 'Teacher responsible for the session';
$string['chooseateacher'] = 'Select the teacher';
$string['recordingurl'] = 'Recorded class (YouTube link)';
$string['recordingurl_help'] = 'After the class ends, paste the YouTube link to make the recording available to students.';
$string['positivevalue'] = 'Enter a positive integer value.';
$string['settingsdescription'] = 'Configure the LiveSonner activity to broadcast live classes.';
$string['eventjoin'] = 'Join the live class';
$string['finalizeclass'] = 'Finish class';
$string['livesonner:addinstance'] = 'Add a new LiveSonner activity to the course';
$string['livesonner:manage'] = 'Manage the LiveSonner live class';
$string['livesonner:view'] = 'View the LiveSonner live class';
$string['attendancealreadyrecorded'] = 'Your attendance has already been recorded.';
$string['classnotstarted'] = 'The class has not started yet.';
$string['classfinished'] = 'Class finished';
$string['joinclass'] = 'Join the class';
$string['countdownmessage'] = 'The class will start in {$a}';
$string['durationlabel'] = '{$a} minutes';
$string['starttimelabel'] = 'Starts at {$a}';
$string['videosectiontitle'] = 'Watch the class recording';
$string['novideoavailable'] = 'No recording has been shared yet.';
$string['attendanceintro'] = 'Click join to record your attendance and access the live class.';
$string['attendanceuser'] = 'Participant';
$string['attendancecount'] = 'Total recorded presences: {$a}';
$string['attendanceempty'] = 'No presences have been recorded yet.';
$string['timeclicked'] = 'Recorded at';
$string['registrationsheading'] = 'Confirmed registrations';
$string['registrationscount'] = 'Total registrations: {$a}';
$string['registrationsempty'] = 'No registrations have been recorded yet.';
$string['registrationuser'] = 'Registered user';
$string['registrationtime'] = 'Registered at';
$string['summarycoursemodule'] = '{$a->date} · {$a->duration} minutes';
$string['attendanceheading'] = 'Recorded attendance';
$string['completionrequirement'] = 'The activity will only be marked as complete when the teacher finishes the class.';
$string['finishsuccess'] = 'The class has been finished and completion is now available.';
$string['finishalready'] = 'The class had already been finished.';
$string['privacy:metadata:livesonner'] = 'Stores details about the live class.';
$string['privacy:metadata:livesonner:course'] = 'Course the class belongs to.';
$string['privacy:metadata:livesonner:name'] = 'Class name.';
$string['privacy:metadata:livesonner:timestart'] = 'Class start time.';
$string['privacy:metadata:livesonner:duration'] = 'Class expected duration.';
$string['privacy:metadata:livesonner:meeturl'] = 'Meeting link.';
$string['privacy:metadata:livesonner:teacherid'] = 'Teacher responsible for the session.';
$string['privacy:metadata:livesonner:recordingurl'] = 'YouTube link with the recorded class.';
$string['privacy:metadata:livesonner_attendance'] = 'Stores records of live attendance.';
$string['privacy:metadata:livesonner_attendance:livesonnerid'] = 'Accessed live class.';
$string['privacy:metadata:livesonner_attendance:userid'] = 'Participating user.';
$string['privacy:metadata:livesonner_attendance:timeclicked'] = 'Moment when join was clicked.';
$string['privacy:metadata:livesonner_enrolments'] = 'Stores registrations created from the live classes dashboard.';
$string['privacy:metadata:livesonner_enrolments:livesonnerid'] = 'Live class associated with the registration.';
$string['privacy:metadata:livesonner_enrolments:userid'] = 'User who registered for the live class.';
$string['privacy:metadata:livesonner_enrolments:timecreated'] = 'Time when the registration was created.';
$string['privacy:metadata:reason'] = 'This data is needed to track attendance and activity completion.';
$string['viewattendance'] = 'View attendance';
$string['joinredirectnotice'] = 'You will be redirected to the classroom.';
$string['videoavailableafterfinish'] = 'Finish the class to unlock the field for adding the YouTube recording link.';
$string['assignedteacher'] = 'Teacher: {$a}';
$string['saverecording'] = 'Save recording link';
$string['recordingsaved'] = 'The recording link was saved successfully.';
$string['invalidrecordingurl'] = 'Provide a valid YouTube link (for example https://www.youtube.com/watch?v=XXXXXXXXXXX).';
$string['recordingformtitle'] = 'Share the recording';
$string['recordingformdescription'] = 'Paste the YouTube link of the recorded class. Leave the field empty to remove the recording.';
$string['recordingurlplaceholder'] = 'https://www.youtube.com/watch?v=XXXXXXXXXXX';
$string['backtocourse'] = 'Back to the course';
$string['nodetails'] = 'No information found for this class.';
$string['painelaulasinvalidsession'] = 'The selected session could not be found.';
$string['painelaulaspermissiondenied'] = 'You do not have permission to enrol this user in the session.';
$string['painelaulasalreadyenrolled'] = 'You are already enrolled in the course for this session.';
$string['painelaulasenrolmentsuccess'] = 'Enrolment completed successfully. You can now access the session.';
$string['painelaulasenrolmentfailure'] = 'Unable to enrol you in the course for this session. Please contact the site administration.';
