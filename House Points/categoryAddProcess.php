<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
use Gibbon\Data\Validator;
use Gibbon\Module\HousePoints\Domain\HousePointCategoryGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/category.php';

if (!isActionAccessible($guid, $connection2, '/modules/House Points/category.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
}

$data = [
    'categoryName'    => trim($_POST['categoryName'] ?? ''),
    'categoryEvent'   => trim($_POST['categoryEvent'] ?? ''),
    'categoryOrder'   => 0,
    'categoryType'    => $_POST['categoryType'] ?? 'House',
    'categoryPresets' => trim($_POST['categoryPresets'] ?? ''),
];

if (empty($data['categoryName']) || empty($data['categoryEvent']) || !in_array($data['categoryType'], ['House', 'Student'], true)) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit();
}

$housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
$housePointCategoryID = $housePointCategoryGateway->insert($data);

$URL .= $housePointCategoryID === false
    ? '&return=error2'
    : '&return=success0';

header("Location: {$URL}");
exit();

?>
