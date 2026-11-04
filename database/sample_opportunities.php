<?php
/**
 * Adds a larger set of fictitious host organizations and attachment opportunities,
 * so the opportunities pages can be demonstrated and tested with realistic volume.
 * Every organization also gets an industrial supervisor account that can accept applicants.
 *
 * All names, emails and phone numbers are fictitious (".test" addresses, 0700 numbers).
 * Safe to run more than once: organizations that already exist are skipped.
 *
 * Run from the command line:  C:\xampp\php\php.exe database\sample_opportunities.php
 * (database\seed.php also runs it.)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line.');
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/test_password.php';

/** [name, sector, county, address, short code, supervisor name, supervisor position, opportunities] */
const SAMPLE_ORGANIZATIONS = [
    ['Demo Rift Valley Bank – ICT Department', 'Banking and Finance', 'Nakuru', 'Kenyatta Avenue, Nakuru', 'rvbank', 'Daniel Kiptoo', 'ICT Operations Manager', [
        ['ICT Help Desk Attachment', "Support bank staff with computer, printer and system problems through the internal help desk.\nLog and follow up support tickets, and set up new workstations.\nSkills: Windows, basic networking, good communication.", 3, 45],
        ['Core Banking Systems Support', "Assist the systems team in monitoring the core banking system, running end-of-day reports and testing system updates.\nSkills: SQL basics, attention to detail, confidentiality.", 2, 60],
        ['Information Security Attachment', "Help the security team review user access rights, check antivirus status and prepare security awareness materials.\nSkills: interest in cybersecurity, networking basics.", 1, 50],
    ]],
    ['Sample Teaching and Referral Hospital', 'Health', 'Nakuru', 'Hospital Road, Nakuru', 'tthospital', 'Mercy Wanjiru', 'Health Records and ICT Officer', [
        ['Health Records Systems Attachment', "Support the health records office in capturing patient data, maintaining the hospital information system and producing monthly reports.\nSkills: data entry accuracy, Microsoft Excel, confidentiality.", 4, 40],
        ['Hospital Network Support', "Help maintain the hospital's local area network, Wi-Fi access points and user computers across departments.\nSkills: networking, cabling, troubleshooting.", 2, 55],
    ]],
    ['Demo Mobile Networks Ltd', 'Telecommunications', 'Nairobi', 'Mombasa Road, Nairobi', 'mobilenet', 'Kevin Mwangi', 'Network Operations Lead', [
        ['Network Operations Centre Attachment', "Monitor network alarms, escalate faults and update incident logs with the network operations team.\nSkills: networking fundamentals, ability to work in shifts.", 4, 70],
        ['Mobile App Testing Attachment', "Test the company's customer mobile app on different phones, record defects and retest fixes.\nSkills: Android, attention to detail, report writing.", 2, 35],
        ['Data Analytics Attachment', "Prepare customer usage reports and dashboards from network data.\nSkills: Microsoft Excel, SQL, interest in data analysis.", 2, 80],
    ]],
    ['Sample Agro Processors Ltd', 'Agriculture', 'Uasin Gishu', 'Kisumu Road, Eldoret', 'agroproc', 'Joseph Kiprop', 'IT Officer', [
        ['IT Support for Farm Operations', "Support staff at the factory and collection centres with computers, printers and the stock system.\nSkills: hardware maintenance, Windows, willingness to travel.", 3, 50],
        ['Farmer Records Database Attachment', "Help clean and update the farmer registration database and produce supplier reports.\nSkills: Microsoft Access or MySQL, Excel.", 2, 65],
    ]],
    ['Demo Software Studio', 'ICT', 'Nairobi', 'Ngong Road, Nairobi', 'swstudio', 'Faith Akinyi', 'Lead Developer', [
        ['Junior Web Developer Attachment', "Build and test web pages for client projects with the development team.\nSkills: HTML, CSS, JavaScript, PHP or another server language.", 3, 30],
        ['Mobile App Development Attachment', "Work on Android app features and fix reported bugs under a senior developer.\nSkills: Java or Kotlin basics, Git.", 2, 45],
        ['Software Quality Assurance Attachment', "Write test cases, carry out manual testing and record defects for web systems before release.\nSkills: attention to detail, report writing.", 2, 60],
        ['UI/UX Design Attachment', "Help design screens and prototypes, and run simple usability tests with users.\nSkills: Figma or similar, creativity.", 1, 75],
    ]],
    ['Sample Lakeside Water and Sanitation Company', 'Other', 'Kisumu', 'Oginga Odinga Street, Kisumu', 'lakewater', 'Brenda Achieng', 'ICT Manager', [
        ['Billing System Support Attachment', "Assist with customer billing data, meter reading uploads and billing reports.\nSkills: Excel, accuracy, customer focus.", 2, 40],
        ['GIS Mapping Attachment', "Help digitise water pipeline and customer locations on maps.\nSkills: interest in GIS, QGIS basics are an advantage.", 1, 85],
    ]],
    ['Demo Coast Logistics Ltd', 'Other', 'Mombasa', 'Moi Avenue, Mombasa', 'coastlog', 'Hassan Omar', 'Systems Administrator', [
        ['Logistics Systems Attachment', "Support the cargo tracking system, user accounts and daily backups.\nSkills: networking, Windows Server basics, databases.", 2, 55],
        ['ICT Support Attachment', "Provide first-line support to office and warehouse staff.\nSkills: troubleshooting, good communication.", 3, 30],
    ]],
    ['Sample Girls High School', 'Education', 'Kericho', 'Kericho–Kisumu Road, Kericho', 'girlshigh', 'Lilian Chepkemoi', 'Head of Computer Studies', [
        ['Computer Laboratory Attachment', "Maintain the school computer laboratory and support Computer Studies lessons.\nSkills: hardware maintenance, Microsoft Office, patience.", 2, 50],
        ['School Records System Attachment', "Help enter and manage student records and examination results in the school system.\nSkills: Excel, data entry accuracy.", 1, 40],
    ]],
    ['Demo Community Health Initiative', 'Non-governmental Organization', 'Kakamega', 'Canon Awori Street, Kakamega', 'commhealth', 'Peter Wekesa', 'Monitoring and Evaluation Officer', [
        ['Data Collection and Reporting Attachment', "Support mobile data collection in the field and prepare monthly programme reports.\nSkills: Excel, mobile data tools, willingness to travel.", 3, 60],
        ['NGO ICT Support Attachment', "Maintain office computers, the shared drive and the organization website.\nSkills: troubleshooting, basic web skills.", 1, 45],
    ]],
    ['Sample Highland Dairy Manufacturers Ltd', 'Manufacturing', 'Nyandarua', 'Ol Kalou Town', 'dairy', 'Samuel Njoroge', 'ERP Systems Officer', [
        ['ERP Systems Attachment', "Help users with the stock and production modules of the company's ERP system and prepare reports.\nSkills: databases, Excel, problem solving.", 2, 70],
        ['Factory IT Support Attachment', "Support computers and barcode scanners on the production floor.\nSkills: hardware, networking basics.", 2, 35],
    ]],
    ['Demo Savings and Credit Co-operative', 'Banking and Finance', 'Kiambu', 'Thika Road, Kiambu', 'sacco', 'Esther Nduta', 'ICT Officer', [
        ['SACCO Systems Support Attachment', "Support the member management system, process data corrections and help members with mobile banking questions.\nSkills: databases, customer care.", 2, 50],
        ['Network and Security Attachment', "Help maintain the branch network, firewall and user accounts.\nSkills: networking, security basics.", 1, 65],
    ]],
    ['Sample County Revenue Office', 'Government', 'Laikipia', 'Nanyuki Town', 'revenue', 'George Maina', 'Revenue Systems Manager', [
        ['Revenue Collection Systems Attachment', "Support the electronic revenue collection system and help traders with online payment issues.\nSkills: Excel, communication, problem solving.", 3, 45],
        ['Government ICT Attachment', "Maintain office computers and support e-government services used by county staff.\nSkills: troubleshooting, Microsoft Office.", 2, 55],
    ]],
    ['Demo Lakeview Hotel and Conference Centre', 'Other', 'Nakuru', 'Naivasha Road, Naivasha', 'hotel', 'Ann Wairimu', 'IT Supervisor', [
        ['Hospitality IT Attachment', "Support the hotel booking system, point-of-sale terminals and guest Wi-Fi.\nSkills: networking, customer service.", 2, 40],
    ]],
    ['Sample Lake Basin Internet Services', 'Telecommunications', 'Kisumu', 'Jomo Kenyatta Highway, Kisumu', 'isp', 'Collins Otieno', 'Technical Manager', [
        ['Fibre Installation and Support Attachment', "Join technicians installing and troubleshooting home and business internet connections.\nSkills: networking, physical fitness for field work.", 4, 30],
        ['Customer Technical Support Attachment', "Help customers solve internet problems by phone and chat, and log support tickets.\nSkills: networking basics, patience, good communication.", 3, 50],
        ['Network Monitoring Attachment', "Monitor the network with the operations team and prepare uptime reports.\nSkills: networking, interest in Linux.", 1, 90],
    ]],
];

function add_sample_opportunities(PDO $pdo, string $hash): array
{
    $added = ['organizations' => 0, 'opportunities' => 0, 'supervisors' => 0];
    $exists = $pdo->prepare('SELECT 1 FROM organizations WHERE name = ?');
    $org = $pdo->prepare("INSERT INTO organizations (name, sector, county, address, contact_email, contact_phone, status, reviewed_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())");
    $user = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'industrial')");
    $sup = $pdo->prepare('INSERT INTO ind_supervisors (user_id, org_id, position) VALUES (?, ?, ?)');
    $opp = $pdo->prepare('INSERT INTO opportunities (org_id, title, description, slots, deadline) VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))');

    $phone = 700000100;
    foreach (SAMPLE_ORGANIZATIONS as [$name, $sector, $county, $address, $code, $supName, $supPos, $opps]) {
        $phone += 2;
        $exists->execute([$name]);
        if ($exists->fetchColumn()) {
            continue;
        }
        $org->execute([$name, $sector, $county, $address, "info@$code.test", '0' . $phone]);
        $orgId = (int) $pdo->lastInsertId();
        $added['organizations']++;

        $user->execute([$supName, "$code@siams.test", '0' . ($phone + 1), $hash]);
        $sup->execute([(int) $pdo->lastInsertId(), $orgId, $supPos]);
        $added['supervisors']++;

        foreach ($opps as [$title, $description, $slots, $days]) {
            $opp->execute([$orgId, $title, $description, $slots, $days]);
            $added['opportunities']++;
        }
    }
    return $added;
}

// Run directly from the command line (not when included by seed.php).
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $pdo = db();
    $pdo->beginTransaction();
    $added = add_sample_opportunities($pdo, password_hash(TEST_PASSWORD, PASSWORD_DEFAULT));
    $pdo->commit();
    printf("Added %d organizations, %d industrial supervisor accounts and %d opportunities.\n",
        $added['organizations'], $added['supervisors'], $added['opportunities']);
}
