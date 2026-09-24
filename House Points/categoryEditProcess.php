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
use Gibbon\Module\HousePoints\Domain\HousePointHouseGateway;
use Gibbon\Module\HousePoints\Domain\HousePointStudentGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/category.php';

if (!isActionAccessible($guid, $connection2, '/modules/House Points/category.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
}

$categoryID = $_POST['categoryID'] ?? '';
$data = [
    'categoryName'    => trim($_POST['categoryName'] ?? ''),
    'categoryEvent'   => trim($_POST['categoryEvent'] ?? ''),
    'categoryType'    => $_POST['categoryType'] ?? 'House',
    'categoryPresets' => trim($_POST['categoryPresets'] ?? ''),
];

$housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
$category = $housePointCategoryGateway->getByID($categoryID);

if (empty($categoryID) || empty($category) || empty($data['categoryName']) || empty($data['categoryEvent']) || !in_array($data['categoryType'], ['House', 'Student'], true)) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit();
}

if ($category['categoryType'] !== $data['categoryType']) {
    $housePointHouseGateway = $container->get(HousePointHouseGateway::class);
    $housePointStudentGateway = $container->get(HousePointStudentGateway::class);
    $housePoints = $housePointHouseGateway->selectBy(['categoryID' => $categoryID])->fetch();
    $studentPoints = $housePointStudentGateway->selectBy(['categoryID' => $categoryID])->fetch();

    if (!empty($housePoints) || !empty($studentPoints)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }
}

$housePointCategoryID = $housePointCategoryGateway->update($categoryID, $data);

$URL .= $housePointCategoryID === false
    ? '&return=error2'
    : '&return=success0';

header("Location: {$URL}");
exit();

?>
