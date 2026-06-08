<?php
require __DIR__ . '/../includes/data.php';
check_access('admin');
$entity = $_GET['entity'] ?? '';
if (!in_array($entity, ['employee', 'client', 'animal'], true)) {
    die('Invalid entity.');
}
if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export_' . $entity . 's_' . date('Ymd_His') . '.csv');
$output = fopen('php://output', 'w');
if ($entity === 'employee') {
    fputcsv($output, ['Name', 'Age', 'Role', 'Started Working', 'Quit Date', 'Status']);
    foreach ($employees as $emp) {
        fputcsv($output, [
            $emp['name'],
            $emp['age'],
            $emp['role'],
            $emp['started'],
            $emp['quit'] ?? 'N/A',
            $emp['quit'] ? 'Inactive' : 'Active'
        ]);
    }
} elseif ($entity === 'client') {
    fputcsv($output, ['Name', 'Email', 'Joined Date', 'Housing Type', 'Phone Number', 'Pets Adopted', 'Returns Count', 'Treatment Status']);
    foreach ($clients as $cli) {
        fputcsv($output, [
            $cli['name'],
            $cli['email'],
            $cli['joined'],
            $cli['housing'] ?? 'Apartment',
            $cli['phone'] ?? 'N/A',
            $cli['adopted'],
            $cli['returned'],
            $cli['status']
        ]);
    }
} elseif ($entity === 'animal') {
    fputcsv($output, ['Name', 'Race/Breed', 'Category', 'Age/Months', 'Sex', 'Image URL', 'Adoption State']);
    foreach ($breeds as $catSlug => $typeBreeds) {
        $categoryName = $animalTypes[$catSlug]['label'] ?? ucfirst($catSlug);
        foreach ($typeBreeds as $breed) {
            $breedName = $breed['name'];
            $breedPets = $pets[$breed['slug']] ?? [];
            foreach ($breedPets as $pet) {
                fputcsv($output, [
                    $pet['name'],
                    $breedName,
                    $categoryName,
                    $pet['age'],
                    $pet['sex'],
                    $pet['image'],
                    'Available'
                ]);
            }
        }
    }
}
fclose($output);
exit;
