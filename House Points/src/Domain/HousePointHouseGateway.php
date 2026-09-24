<?php
namespace Gibbon\Module\HousePoints\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * House Points Gateway
 *
 * @version v20
 * @since   v20
 */
class HousePointHouseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'hpPointHouse';
    private static $primaryKey = 'hpID';
    private static $searchableColumns = [];

    /**
     * Returns a paginated DataSet of all student and house point records whose
     * category belongs to the given event name, for the given school year.
     *
     * @param  QueryCriteria $criteria
     * @param  string|int    $yearID
     * @param  string        $categoryEvent  Trimmed event name from hpCategory.categoryEvent
     * @return \Gibbon\Domain\DataSet
     */
    public function queryEventsByCategoryEvent(QueryCriteria $criteria, $yearID, $categoryEvent)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'hpCategory.categoryName AS categoryName',
                "COALESCE(gibbonHouse.name, '') AS houseName",
                'hpPointStudent.points AS points',
                'hpPointStudent.activity AS activity',
                'hpPointStudent.awardedDate AS awardedDate',
                "CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) AS studentName",
            ])
            ->from('hpPointStudent')
            ->innerJoin('hpCategory',  'hpPointStudent.categoryID  = hpCategory.categoryID')
            ->innerJoin('gibbonPerson', 'hpPointStudent.studentID   = gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonHouse',  'gibbonPerson.gibbonHouseID = gibbonHouse.gibbonHouseID')
            ->where('hpPointStudent.yearID = :yearID')
            ->bindValue('yearID', $yearID)
            ->where('TRIM(hpCategory.categoryEvent) = :categoryEvent')
            ->bindValue('categoryEvent', trim($categoryEvent));

        $this->unionAllWithCriteria($query, $criteria)
            ->cols([
                'hpCategory.categoryName AS categoryName',
                'gibbonHouse.name AS houseName',
                'hpPointHouse.points AS points',
                'hpPointHouse.activity AS activity',
                'hpPointHouse.awardedDate AS awardedDate',
                'NULL AS studentName',
            ])
            ->from('hpPointHouse')
            ->innerJoin('hpCategory', 'hpPointHouse.categoryID = hpCategory.categoryID')
            ->innerJoin('gibbonHouse', 'hpPointHouse.houseID   = gibbonHouse.gibbonHouseID')
            ->where('hpPointHouse.yearID = :yearID')
            ->bindValue('yearID', $yearID)
            ->where('TRIM(hpCategory.categoryEvent) = :categoryEvent')
            ->bindValue('categoryEvent', trim($categoryEvent));

        return $this->runQuery($query, $criteria);
    }

    public function selectAllPoints($gibbonSchoolYearID) {
        $select = $this
            ->newSelect()
            ->from('gibbonHouse')
            ->cols(['gibbonHouse.gibbonHouseID AS houseID', 'gibbonHouse.name AS houseName', 'gibbonHouse.logo as houseLogo','COALESCE(pointStudent.total + pointHouse.total, pointStudent.total, pointHouse.total, 0) AS total'])
            ->joinSubSelect(
                'left',
                'SELECT gibbonPerson.gibbonHouseID AS houseID,
                    SUM(hpPointStudent.points) AS total
                    FROM hpPointStudent
                    INNER JOIN gibbonPerson
                    ON hpPointStudent.studentID = gibbonPerson.gibbonPersonID
                    WHERE hpPointStudent.yearID=:gibbonSchoolYearID
                    GROUP BY gibbonPerson.gibbonHouseID ',
                'pointStudent',
                'pointStudent.houseID = gibbonHouse.gibbonHouseID'
            )->joinSubSelect(
                'left',
                'SELECT hpPointHouse.houseID,
                        SUM(hpPointHouse.points) AS total
                        FROM hpPointHouse
                        WHERE hpPointHouse.yearID=:gibbonSchoolYearID
                        GROUP BY hpPointHouse.houseID',
                'pointHouse', 
                'pointHouse.houseID = gibbonHouse.gibbonHouseID'
            )
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->orderBy(['total DESC']);

        return $this->runSelect($select);
    }
    
    public function selectHousePoints($houseID, $gibbonSchoolYearID) {
        $select = $this
            ->newSelect()
            ->from('hpPointHouse')
            ->cols(['hpPointHouse.hpID', 'DATE_FORMAT(hpPointHouse.awardedDate, \'%d/%m/%Y\') AS awardedDate','hpPointHouse.points', 'hpCategory.categoryName','hpPointHouse.activity', 'CONCAT(gibbonPerson.title, \' \', gibbonPerson.preferredName, \' \', gibbonPerson.surname) AS teacherName'])
            ->innerJoin('hpCategory','hpCategory.categoryID = hpPointHouse.categoryID')
            ->innerJoin('gibbonPerson','gibbonPerson.gibbonPersonID = hpPointHouse.awardedBy')
            ->innerJoin('gibbonHouse','gibbonHouse.gibbonHouseID = hpPointHouse.houseID')
            ->where('hpPointHouse.houseID = :houseID')
            ->bindValue('houseID', $houseID)
            ->where('hpPointHouse.yearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->orderBy(['hpPointHouse.awardedDate']);

        return $this->runSelect($select);
    }

    public function selectHouseSchoolYears() {
        $sql = "SELECT DISTINCT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name, gibbonSchoolYear.sequenceNumber
                FROM gibbonSchoolYear
                JOIN ((SELECT hpPointHouse.yearID FROM hpPointHouse)
                UNION 
                (SELECT hpPointStudent.yearID FROM hpPointStudent)) AS houseYears ON houseYears.yearID = gibbonSchoolYear.gibbonSchoolYearID
                WHERE gibbonSchoolYear.status <> 'Current'
                ORDER BY gibbonSchoolYear.sequenceNumber DESC;";
        
        return $this->db()->select($sql);
    }
}
