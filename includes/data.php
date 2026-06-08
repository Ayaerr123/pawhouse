<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
$center = [
    'name'     => 'pawHouse Adoption Center',
    'tagline'  => 'Careful matches for animals who need a steady home.',
    'location' => 'Avenue Mohammed V, Al Hoceima, Morocco',
    'phone'    => '+212 522 000 418',
    'email'    => 'contact@pawhouse-center.test',
    'hours'    => 'Monday to Saturday, 09:00 - 18:00',
];
$pdo = get_pdo();
$animalTypes = [];
$stmtCats = $pdo->query(
    'SELECT ac.id, ac.slug, ac.name, ac.description, ac.image_url,
            COUNT(DISTINCT CASE WHEN a.adoption_state = \'available\' THEN a.id END) AS available_count,
            COUNT(DISTINCT CASE WHEN a.adoption_state = \'adopted\'   THEN a.id END) AS adopted_count
     FROM animal_categories ac
     LEFT JOIN animals a ON a.category_id = ac.id
     GROUP BY ac.id, ac.slug, ac.name, ac.description, ac.image_url
     ORDER BY ac.id'
);
foreach ($stmtCats->fetchAll() as $row) {
    $animalTypes[$row['slug']] = [
        'id'          => (int)$row['id'],
        'label'       => $row['name'],
        'description' => $row['description'],
        'image'       => $row['image_url'] ?? '',
        'available'   => (int)$row['available_count'],
        'adopted'     => (int)$row['adopted_count'],
    ];
}
$breeds = [];
$stmtBreeds = $pdo->query(
    'SELECT ab.id, ab.slug, ab.name, ab.image_url, ab.fact, ac.slug AS cat_slug,
            COUNT(DISTINCT CASE WHEN a.adoption_state = \'available\' THEN a.id END) AS available_count,
            COUNT(DISTINCT CASE WHEN a.adoption_state = \'adopted\'   THEN a.id END) AS adopted_count
     FROM animal_breeds ab
     JOIN animal_categories ac ON ac.id = ab.category_id
     LEFT JOIN animals a ON a.breed_id = ab.id
     GROUP BY ab.id, ab.slug, ab.name, ab.image_url, ab.fact, ac.slug
     ORDER BY ab.id'
);
foreach ($stmtBreeds->fetchAll() as $row) {
    $breeds[$row['cat_slug']][] = [
        'id'        => (int)$row['id'],
        'slug'      => $row['slug'],
        'name'      => $row['name'],
        'image'     => $row['image_url'] ?? '',
        'fact'      => $row['fact'],
        'available' => (int)$row['available_count'],
        'adopted'   => (int)$row['adopted_count'],
    ];
}
$pets = [];
$stmtPets = $pdo->query(
    'SELECT a.id, a.name, a.age_months, a.sex, a.image_url, ab.slug AS breed_slug
     FROM animals a
     JOIN animal_breeds ab ON ab.id = a.breed_id
     WHERE a.adoption_state = \'available\'
     ORDER BY a.id'
);
foreach ($stmtPets->fetchAll() as $row) {
    $months = (int)$row['age_months'];
    if ($months < 12) {
        $ageStr = $months . ' month' . ($months !== 1 ? 's' : '');
    } else {
        $years = intdiv($months, 12);
        $ageStr = $years . ' year' . ($years !== 1 ? 's' : '');
    }
    $pets[$row['breed_slug']][(int)$row['id']] = [
        'id'    => (int)$row['id'],
        'name'  => $row['name'],
        'age'   => $ageStr,
        'sex'   => ucfirst($row['sex']),
        'image' => $row['image_url'] ?? '',
    ];
}
$employees = [];
$stmtEmp = $pdo->query(
    'SELECT u.id AS user_id, e.id AS emp_id, u.full_name, e.age, e.role_title, e.started_working, e.quitting_date
     FROM employees e
     JOIN users u ON u.id = e.user_id
     ORDER BY e.id'
);
foreach ($stmtEmp->fetchAll() as $row) {
    $employees[(int)$row['emp_id']] = [
        'id'       => (int)$row['emp_id'],
        'user_id'  => (int)$row['user_id'],
        'name'     => $row['full_name'],
        'age'      => (int)$row['age'],
        'role'     => $row['role_title'],
        'started'  => $row['started_working'],
        'quit'     => $row['quitting_date'],
    ];
}
$clients = [];
$stmtCli = $pdo->query(
    'SELECT u.id AS user_id, c.id AS client_id, u.full_name, u.email, u.password_visible_to_admin,
            c.phone, c.housing_type, c.became_client_at, c.notes,
            COUNT(DISTINCT CASE WHEN ar.status IN (\'approved\',\'delivered\') THEN ar.id END) AS adopted_count,
            COUNT(DISTINCT CASE WHEN ar.status = \'returned\'                 THEN ar.id END) AS returned_count
     FROM clients c
     JOIN users u ON u.id = c.user_id
     LEFT JOIN adoption_requests ar ON ar.client_id = c.id
     GROUP BY u.id, c.id, u.full_name, u.email, u.password_visible_to_admin,
              c.phone, c.housing_type, c.became_client_at, c.notes
     ORDER BY c.id'
);
foreach ($stmtCli->fetchAll() as $row) {
    $clients[(int)$row['client_id']] = [
        'id'       => (int)$row['client_id'],
        'user_id'  => (int)$row['user_id'],
        'name'     => $row['full_name'],
        'email'    => $row['email'],
        'password' => $row['password_visible_to_admin'],
        'joined'   => $row['became_client_at'],
        'adopted'  => (int)$row['adopted_count'],
        'returned' => (int)$row['returned_count'],
        'status'   => $row['notes'] ?? 'New client',
        'phone'    => $row['phone'],
        'housing'  => $row['housing_type'],
    ];
}
$adoptionDemands = [];
$stmtDem = $pdo->query(
    'SELECT ar.id, u.full_name AS client_name, a.name AS pet_name, ar.status,
            ar.delivered_at, ar.approved_at,
            (SELECT COUNT(*) FROM return_requests rr WHERE rr.adoption_request_id = ar.id AND rr.status = \'open\') AS open_returns,
            ar.employee_note
     FROM adoption_requests ar
     JOIN clients c ON c.id = ar.client_id
     JOIN users u ON u.id = c.user_id
     JOIN animals a ON a.id = ar.animal_id
     ORDER BY ar.id'
);
foreach ($stmtDem->fetchAll() as $row) {
    $state = match($row['status']) {
        'pending'      => 'Under review',
        'under_review' => 'Under review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected',
        'delivered'    => 'Delivered',
        'returned'     => 'Delivered',
        default        => ucfirst($row['status']),
    };
    if ($row['status'] === 'delivered' || $row['status'] === 'returned') {
        $delivery = $row['delivered_at'] ? date('Y-m-d', strtotime($row['delivered_at'])) : 'Delivered';
    } elseif ($row['status'] === 'approved' && $row['employee_note']) {
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $row['employee_note'], $m)) {
            $delivery = 'Scheduled for ' . $m[0];
        } else {
            $delivery = 'Scheduled';
        }
    } else {
        $delivery = 'Not delivered';
    }
    $returnStatus = ((int)$row['open_returns'] > 0) ? 'Return Demanded' : 'No return request';
    $adoptionDemands[] = [
        'id'       => (int)$row['id'],
        'client'   => $row['client_name'],
        'pet'      => $row['pet_name'],
        'state'    => $state,
        'delivery' => $delivery,
        'return'   => $returnStatus,
    ];
}
$meetings = [];
$stmtMtg = $pdo->query(
    'SELECT fm.id, fm.meeting_number, fm.scheduled_for, fm.completed_at,
            fm.treatment_notes, fm.return_required, fm.animal_condition,
            u.full_name AS client_name, a.name AS pet_name
     FROM follow_up_meetings fm
     JOIN adoption_requests ar ON ar.id = fm.adoption_request_id
     JOIN clients c ON c.id = ar.client_id
     JOIN users u ON u.id = c.user_id
     JOIN animals a ON a.id = ar.animal_id
     ORDER BY fm.id'
);
foreach ($stmtMtg->fetchAll() as $row) {
    $meetings[] = [
        'id'              => (int)$row['id'],
        'week'            => (int)$row['meeting_number'],
        'date'            => $row['scheduled_for'],
        'client'          => $row['client_name'],
        'pet'             => $row['pet_name'],
        'note'            => $row['treatment_notes'] ?? '',
        'completed'       => !is_null($row['completed_at']),
        'return_required' => (bool)$row['return_required'],
    ];
}
$surrenderRequests = [];
$stmtSurr = $pdo->query(
    'SELECT sr.id, u.full_name AS client_name, ac.slug AS type_slug, sr.race,
            sr.pet_name, sr.age, sr.sex, sr.image_path, sr.info,
            sr.dropoff_date, sr.status, sr.submitted_at
     FROM surrender_requests sr
     JOIN clients c ON c.id = sr.client_id
     JOIN users u ON u.id = c.user_id
     JOIN animal_categories ac ON ac.id = sr.category_id
     ORDER BY sr.id DESC'
);
foreach ($stmtSurr->fetchAll() as $row) {
    $surrenderRequests[] = [
        'id'           => (int)$row['id'],
        'client'       => $row['client_name'],
        'type_slug'    => $row['type_slug'],
        'race'         => $row['race'],
        'pet_name'     => $row['pet_name'],
        'age'          => $row['age'],
        'sex'          => $row['sex'],
        'image'        => $row['image_path'],
        'info'         => $row['info'],
        'dropoff_date' => $row['dropoff_date'],
        'status'       => $row['status'],
        'submitted_at' => $row['submitted_at'],
    ];
}
if (isset($_SESSION['admin_edits']['clients'])) {
    foreach ($_SESSION['admin_edits']['clients'] as $cid => $cdata) {
        if (isset($clients[$cid])) {
            $clients[$cid] = array_merge($clients[$cid], $cdata);
        }
    }
}
if (isset($_SESSION['admin_edits']['employees'])) {
    foreach ($_SESSION['admin_edits']['employees'] as $eid => $edata) {
        if (isset($employees[$eid])) {
            $employees[$eid] = array_merge($employees[$eid], $edata);
        }
    }
}
if (isset($_SESSION['admin_edits']['animalTypes'])) {
    foreach ($_SESSION['admin_edits']['animalTypes'] as $slug => $tdata) {
        if (isset($animalTypes[$slug])) {
            $animalTypes[$slug] = array_merge($animalTypes[$slug], $tdata);
        }
    }
}
if (isset($_SESSION['admin_edits']['pets'])) {
    foreach ($_SESSION['admin_edits']['pets'] as $breedSlug => $breedPets) {
        foreach ($breedPets as $pid => $pdata) {
            if (isset($pets[$breedSlug][$pid])) {
                $pets[$breedSlug][$pid] = array_merge($pets[$breedSlug][$pid], $pdata);
            }
        }
    }
}
$uri    = $_SERVER['REQUEST_URI'];
$script = basename($_SERVER['SCRIPT_NAME']);
if (strpos($uri, '/admin/') !== false) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_role']  = 'admin';
        $_SESSION['user_email'] = 'admin@pawhouse.test';
        $_SESSION['user_id']    = 1;
    }
} elseif (strpos($uri, '/employee/') !== false) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
        $_SESSION['user_role']  = 'employee';
        $_SESSION['user_email'] = 'salma@pawhouse.test';
        foreach ($employees as $e) {
            if (strtolower($e['name']) === 'salma berrada') {
                $_SESSION['user_id'] = $e['user_id'];
                break;
            }
        }
    }
} elseif (strpos($uri, '/client/') !== false || $script === 'register.php') {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
        $_SESSION['user_role']  = 'client';
        $_SESSION['user_email'] = 'nadia@example.test';
        foreach ($clients as $cl) {
            if (strcasecmp($cl['email'], 'nadia@example.test') === 0) {
                $_SESSION['user_id'] = $cl['user_id'];
                break;
            }
        }
    }
}
function adoption_rate(int $adopted, int $available): int
{
    $total = $adopted + $available;
    return $total > 0 ? (int) round(($adopted / $total) * 100) : 0;
}
function current_page(): string
{
    return basename($_SERVER['SCRIPT_NAME']);
}
function resolve_image(string $url): string
{
    global $basePath;
    if (strpos($url, 'commons.wikimedia.org/wiki/Special:FilePath/') !== false) {
        $filename = urldecode(basename(parse_url($url, PHP_URL_PATH)));
        $filename = str_replace(' ', '_', $filename);
        $md5      = md5($filename);
        return 'https://upload.wikimedia.org/wikipedia/commons/'
             . $md5[0] . '/' . $md5[0] . $md5[1] . '/'
             . rawurlencode($filename);
    }
    if (!empty($url) && strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $prefix = isset($basePath) ? $basePath . '/' : '';
        return $prefix . $url;
    }
    return $url;
}
if (!function_exists('check_access')) {
    function check_access(string $requiredRole): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role  = $_SESSION['user_role']  ?? '';
        $email = $_SESSION['user_email'] ?? '';
        if (empty($email)) {
            $projRoot  = str_replace('\\', '/', realpath(dirname(__DIR__)));
            $scriptDir = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
            $relPath = '';
            if (strpos($scriptDir, $projRoot) === 0) {
                $sub   = ltrim(substr($scriptDir, strlen($projRoot)), '/\\');
                $parts = array_filter(explode('/', str_replace('\\', '/', $sub)));
                if (count($parts) > 0) {
                    $relPath = str_repeat('../', count($parts));
                }
            }
            header('Location: ' . $relPath . 'login.php?role=' . $requiredRole . '&error=unauthorized');
            exit;
        }
        if ($role !== $requiredRole) {
            $projRoot  = str_replace('\\', '/', realpath(dirname(__DIR__)));
            $scriptDir = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
            $relPath = '';
            if (strpos($scriptDir, $projRoot) === 0) {
                $sub   = ltrim(substr($scriptDir, strlen($projRoot)), '/\\');
                $parts = array_filter(explode('/', str_replace('\\', '/', $sub)));
                if (count($parts) > 0) {
                    $relPath = str_repeat('../', count($parts));
                }
            }
            $dashboardLinks = [
                'client'   => $relPath . 'client/dashboard.php',
                'employee' => $relPath . 'employee/dashboard.php',
                'admin'    => $relPath . 'admin/dashboard.php',
            ];
            $myDashboard  = $dashboardLinks[$role] ?? $relPath . 'index.php';
            $logoutUrl    = $relPath . 'logout.php';
            $roleLabel    = ucfirst($requiredRole);
            $myRoleLabel  = ucfirst($role);
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
            echo '<title>Access Denied - pawHouse</title>';
            echo '<style>';
            echo 'body{font-family:system-ui,sans-serif;background:#f9f7f4;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}';
            echo '.box{background:#fff;border-radius:16px;padding:48px 40px;max-width:460px;width:100%;box-shadow:0 4px 32px rgba(0,0,0,.08);text-align:center;}';
            echo '.icon{font-size:52px;margin-bottom:16px;}';
            echo 'h1{font-size:22px;margin:0 0 10px;color:#111;}';
            echo 'p{color:#666;font-size:15px;line-height:1.5;margin:0 0 24px;}';
            echo '.btn{display:inline-block;padding:10px 22px;border-radius:8px;font-size:14px;font-weight:700;text-decoration:none;margin:4px;}';
            echo '.primary{background:#3d7a4f;color:#fff;} .secondary{background:#f0ebe3;color:#333;}';
            echo '</style></head><body>';
            echo '<div class="box">';
            echo '<h1>Access Denied</h1>';
            echo '<p>You are signed in as a <strong>' . htmlspecialchars($myRoleLabel) . '</strong> account.<br>This area is restricted to <strong>' . htmlspecialchars($roleLabel) . '</strong> accounts only.</p>';
            echo '<a class="btn primary" href="' . htmlspecialchars($myDashboard) . '">Go to My Dashboard</a>';
            echo '<a class="btn secondary" href="' . htmlspecialchars($logoutUrl) . '">Logout</a>';
            echo '</div></body></html>';
            exit;
        }
    }
}
?>