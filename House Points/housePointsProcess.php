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
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Module\HousePoints\Domain\HousePointCategoryGateway;
use Gibbon\Module\HousePoints\Domain\HousePointHouseGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/house.php';

if (!isActionAccessible($guid, $connection2, '/modules/House Points/house.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    $data = [
        'houseID'     => $_POST['houseID'] ?? '',
        'categoryID'  => $_POST['categoryID'] ?? '',
        'points'      => filter_var($_POST['points'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]),
        'activity'    => trim($_POST['activity'] ?? ''),
        'yearID'      => $session->get('gibbonSchoolYearID') ?? '',
        'awardedDate' => date('Y-m-d'),
        'awardedBy'   => $session->get('gibbonPersonID'),
    ];

    $houseGateway = $container->get(HouseGateway::class);
    $housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
    $house = $houseGateway->getByID($data['houseID']);
    $category = $housePointCategoryGateway->selectBy(['categoryID' => $data['categoryID'], 'categoryType' => 'House'])->fetch();

    if (empty($data['houseID']) || empty($data['categoryID']) || $data['points'] === false || empty($data['activity']) || empty($house) || empty($category)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $unlimitedPoints = getHighestGroupedAction($guid, '/modules/House Points/house.php', $connection2) === 'Award house points_unlimited';
    $pointsAreValid = $unlimitedPoints;

    foreach (explode(',', $category['categoryPresets'] ?? '') as $preset) {
        $presetValues = explode(':', $preset);
        $presetPoints = trim(end($presetValues));

        if (ctype_digit($presetPoints) && (int) $presetPoints === $data['points']) {
            $pointsAreValid = true;
            break;
        }
    }

    if (!$pointsAreValid) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $housePointHouseGateway = $container->get(HousePointHouseGateway::class);
    $housePointHouseID = $housePointHouseGateway->insert($data);

    $URL .= $housePointHouseID === false
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
    exit();
}

?>
