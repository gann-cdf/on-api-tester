<?php

require_once './vendor/autoload.php';

use Battis\BootstrapSmarty\BootstrapSmarty;
use GannCDF\OnAPITester\OnAPI;

$config = json_decode(file_get_contents('./config.json'), true);

$api = new OnAPI($config['api']);

$smarty = BootstrapSmarty::getSmarty('./templates');
$smarty->assign([
    'title' => 'Blackbaud ON API Tester',
    'config' => $config
]);

if (!empty($_REQUEST['username']) && !empty($_REQUEST['password'])) {
    $tests = [$api->authenticate($_REQUEST)];

    // User
    $tests[] = $api->getIfUser('user/:UserId');
    $tests[] = $api->getIfUser('user/extended/:UserId');
    $tests[] = $api->getIfUser('user/extended/all/:UserId');

    // User address
    $tests[] = $api->get('user/address/types', ['TypeId' => 'AddressTypeId']);
    $tests[] = $api->getIf([
        'UserId' => 'userID',
        'AddressTypeId' => 'type'
    ], 'user/address', ['AddressId']);
    $tests[] = $api->getIf(['UserId' => 'userID'], 'user/address/all', ['AddressId']);
    $tests[] = $api->getIf(['AddressId'], 'user/address/share/:AddressId');

    // User education
    $tests[] = $api->getIfUser('user/education');
    $tests[] = $api->getIfUser('user/education/all');

    // User emergency contacts
    $tests[] = $api->getIfUser('user/emergencycontactemail');
    $tests[] = $api->getIfUser('user/emergencycontactphone');
    $tests[] = $api->get(
        'datasync/EmergencyContactChangesGet',
        ['lastUpdateDate' => '01/01/2018']
    );

    // User occupation
    $tests[] = $api->getIfUser('user/occupation');
    $tests[] = $api->getIfUser('user/occupation/all');

    // User phones
    $tests[] = $api->get('user/phone/types', ['TypeId' => 'PhoneTypeId']);
    $tests[] = $api->getIfUser('user/phone', ['PhoneId']);
    $tests[] = $api->getIfUser('user/phone/all', ['PhoneId']);
    $tests[] = $api->getIf(['PhoneId'], 'user/phone/share/:PhoneId');

    // User relationships
    $tests[] = $api->getIfUser('user/relationshipsettings/:UserId');

    // User roles
    $tests[] = $api->get('role/ListAll', ['RoleId']);
    $tests[] = $api->getIf(['RoleId' => 'roleIDs'], 'user/all');

    // User audit
    $tests[] = $api->getIf([
        'RoleId' => 'roleID',
        'RoleId' => 'baseRoleID'
    ], 'user/audit', [
        'startDate' => '2018-01-01',
        'endDate' => '2018-02-01',
        'report' => 0
    ]);

    // School info
    $tests[] = $api->get(
        'schoolinfo/allschoolyears',
        ['SchoolYearId', 'SchoolYearLabel']
    );
    $tests[] = $api->getIf(['SchoolYearId' => 'schoolYear'], 'schoolinfo/term');
    $tests[] = $api->get('schoolinfo/schoollevel', ['Id' => 'SchoolLevelId']);
    $tests[] = $api->get('schoolinfo/gradelevel', ['grade_id']);

    // Academic courses
    $tests[] = $api->get('academics/course');
    $tests[] = $api->get('academics/department');
    $tests[] = $api->getIf([
        'SchoolYearId' => 'schoolYear',
        'SchoolLevelId' => 'levelNum'
    ], 'academics/section', ['Id' => 'SectionId']);
    $tests[] = $api->getIf([
        'SectionId' => 'sectionID',
        'LeadSectionId'
    ], 'academics/enrollment');

    // Assignments
    $tests[] = $api->getIf(
        ['LeadSectionId' => 'leadSectionId'],
        'academics/assignment',
        ['AssignmentId']
    );
    $tests[] = $api->getIf(
        ['SectionId'],
        'assignment/forsection/:SectionId',
        ['AssignmentId']
    );
    $tests[] = $api->getIf([
        'AssignmentId' => 'assignmentId',
        'SectionId' => 'sectionId'
    ], 'academics/AssignmentGrade');
    $tests[] = $api->get('AssignmentType/All');
    $tests[] = $api->getIf(
        ['SectionId' => 'sectionId'],
        'assignment/TypesForSection'
    );

    // Admissions
    $tests[] = $api->get('AdmChecklist/List');

    // Athletics
    $tests[] = $api->get('athletics/sport');
    $tests[] = $api->getIf(
        ['SchoolYearLabel' => 'schoolyearlabel'],
        'athletics/team',
        ['Id' => 'TeamId']
    );
    $tests[] = $api->getIf(['TeamId' => 'teamId'], 'athletics/roster');
    $tests[] = $api->getIf(['TeamId'], 'athletics/scoreboard/:TeamId');
    $tests[] = $api->getIf(['LocationId'], 'athletics/location/:LocationId');

    // Lists
    $tests[] = $api->getIf(['ListId'], 'list/:ListId');

    $smarty->assign([
        'subtitle' => $_REQUEST['username'],
        'tests' => $tests
    ]);
    $smarty->display('tests.tpl');
} else {
    $smarty->display('login.tpl');
}
