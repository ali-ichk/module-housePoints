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
include  '../../gibbon.php';

$dbh = $connection2;

$houseID = $_POST['houseID'];

$data = [
    'houseID' => $houseID,
    'yearID' => $session->get('gibbonSchoolYearID'),
];
$sql = "SELECT hpPointHouse.hpID, 
    DATE_FORMAT(hpPointHouse.awardedDate, '%d/%m/%Y') AS awardedDate,
    hpPointHouse.points, 
    hpCategory.categoryName,
    hpPointHouse.activity, 
    CONCAT(gibbonPerson.title, ' ', gibbonPerson.preferredName, ' ', gibbonPerson.surname) AS teacherName
    FROM hpPointHouse
    INNER JOIN hpCategory
    ON hpCategory.categoryID = hpPointHouse.categoryID
    INNER JOIN gibbonPerson
    ON gibbonPerson.gibbonPersonID = hpPointHouse.awardedBy
    WHERE hpPointHouse.houseID = :houseID
    AND hpPointHouse.yearID = :yearID
    ORDER BY hpPointHouse.awardedDate DESC";
$rs = $dbh->prepare($sql);
$rs->execute($data);
$points = $rs->fetchAll();

$res = array(
    'points' => $points
);
echo json_encode($res);
