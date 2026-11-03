<?php
/**
 * Adds fictitious test accounts and sample data for development and testing.
 * No real student or staff data is used (see Section 3.12 of the proposal).
 * Run from the command line only:  C:\xampp\php\php.exe database\seed.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line.');
}
require_once __DIR__ . '/../config/db.php';

// All test accounts share this password. Listed in database/TEST_ACCOUNTS.md.
const TEST_PASSWORD = 'Siams@2026';

$pdo  = db();
$hash = password_hash(TEST_PASSWORD, PASSWORD_DEFAULT);

$pdo->beginTransaction();

$addUser = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)');
function add_user(PDOStatement $stmt, string $hash, string $name, string $email, string $phone, string $role): int
{
    $stmt->execute([$name, $email, $phone, $hash, $role]);
    return (int) db()->lastInsertId();
}

// Host organizations (fictitious)
$org = $pdo->prepare("INSERT INTO organizations (name, sector, county, address, contact_email, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
$org->execute(['Demo Tech Solutions Ltd', 'ICT', 'Nakuru', 'Kenyatta Avenue, Nakuru', 'hr@demotech.test', '0700000001']);
$org1 = (int) $pdo->lastInsertId();
$org->execute(['Sample County ICT Office', 'Government', 'Nakuru', 'Nakuru Town', 'ict@samplecounty.test', '0700000002']);

// One account per role
$adminId = add_user($addUser, $hash, 'System Administrator', 'admin@siams.test', '0700000010', 'admin');

$coordId = add_user($addUser, $hash, 'Grace Coordinator', 'coordinator@siams.test', '0700000011', 'coordinator');
$pdo->prepare('INSERT INTO coordinators (user_id, staff_no, school) VALUES (?, ?, ?)')
    ->execute([$coordId, 'KU-STF-001', 'School of Science, Engineering & Technology']);

$lectId = add_user($addUser, $hash, 'Peter Lecturer', 'lecturer@siams.test', '0700000012', 'lecturer');
$pdo->prepare('INSERT INTO lecturers (user_id, staff_no, department) VALUES (?, ?, ?)')
    ->execute([$lectId, 'KU-STF-002', 'Computer Science']);

$indId = add_user($addUser, $hash, 'Jane Industrial', 'industrial@siams.test', '0700000013', 'industrial');
$pdo->prepare('INSERT INTO ind_supervisors (user_id, org_id, position) VALUES (?, ?, ?)')
    ->execute([$indId, $org1, 'ICT Manager']);

$stuId = add_user($addUser, $hash, 'Brian Student', 'student@siams.test', '0700000014', 'student');
$pdo->prepare('INSERT INTO students (user_id, reg_no, programme, year_of_study) VALUES (?, ?, ?, ?)')
    ->execute([$stuId, 'DIT/N/0001/09/25', 'Diploma in Information Technology', 2]);

// An inactive account, used to test that deactivated users cannot log in.
add_user($addUser, $hash, 'Inactive Student', 'inactive@siams.test', '0700000015', 'student');
$pdo->prepare("UPDATE users SET status = 'inactive' WHERE email = 'inactive@siams.test'")->execute();

// A sample attachment period, set as the active one.
$pdo->prepare("INSERT INTO attachment_periods (name, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, 1, ?)")
    ->execute(['January - April 2027', '2027-01-04', '2027-03-26', $coordId]);

// Two sample opportunities (fictitious), open for 60 days.
$opp = $pdo->prepare('INSERT INTO opportunities (org_id, title, description, slots, deadline) VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 60 DAY))');
$opp->execute([$org1, 'ICT Support Attachment',
    "Assist the ICT team with computer maintenance, user support and network troubleshooting.\nSkills: basic networking, Windows, good communication.", 3]);
$opp->execute([$org1, 'Web Development Attachment',
    "Work with the development team on the company website and internal web tools.\nSkills: HTML, CSS, PHP and MySQL.", 2]);

$pdo->commit();
echo "Test data added. Password for every test account: see database/TEST_ACCOUNTS.md\n";
