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

$categoryID = $_POST['categoryID'] ?? '';

if (empty($categoryID)) {
    exit();
}

$housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
$category = $housePointCategoryGateway->getByID($categoryID);

if (empty($category)) {
    exit();
}

$action = $category['categoryType'] === 'House' ? '/modules/House Points/house.php' : '/modules/House Points/award.php';

if (!isActionAccessible($guid, $connection2, $action)) {
    exit();
}

$presetsText = $category['categoryPresets'] ?? '';
if (empty($presetsText)) {
    exit();
}

$presets = [];
foreach (array_map('trim', explode(',', $presetsText)) as $index => $preset) {
    [$name, $points] = array_pad(array_map('trim', explode(':', $preset)), 2, false);
    $presets[$points . chr($index + 65)] = $name !== $points ? $name . ': ' . $points . ' points' : $points . ' points';
}

echo json_encode($presets);
exit();
