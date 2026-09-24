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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Module\HousePoints\Domain\HousePointCategoryGateway;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Award student points'));
if (isActionAccessible($guid, $connection2,"/modules/House Points/award.php")==FALSE) {
    // Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
        $form = Form::create('awardForm', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/studentPointsProcess.php', 'post');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Award student points'));
        $form->addHiddenValue('address', $session->get('address'));
    
        // Select Multiple Students
        $row = $form->addRow();
            $col = $row->addColumn();
                $col->addLabel('students', __('Students'));
                $col->addSelectUsers('students', $session->get('gibbonSchoolYearID'), ['includeStudents' => true, 'useMultiSelect' => true])
                    ->required()
                    ->mergeGroupings();

        $highestAction = getHighestGroupedAction($guid, '/modules/House Points/award.php', $connection2);
        $unlimitedPoints = ($highestAction == 'Award student points_unlimited');
        
        $housePointCategoryGateway = $container->get(HousePointCategoryGateway::class);
        $hpCategories = $housePointCategoryGateway->selectBy(['categoryType' => 'Student']);
        $categories = array_reduce($hpCategories->fetchAll(), function($group, $item) use ($unlimitedPoints) { 
            if (empty($item['categoryPresets']) && !$unlimitedPoints) return $group; 

            $group[$item['categoryID']] = $item['categoryName'];
            return $group;
        }, array());

        $row = $form->addRow();
            $row->addLabel('categoryID', __('Category'));
            $row->addSelect('categoryID')->fromArray($categories)->required()->placeholder();

        $row = $form->addRow();
            $row->addLabel('points', __('Points'));
            $row->addTextField('points')->disabled()->placeholder(__('Select a category'));

        $activities = $pdo->select("SELECT DISTINCT activity FROM hpPointHouse UNION SELECT DISTINCT activity from hpPointStudent ORDER BY activity")->fetchAll(\PDO::FETCH_COLUMN);
        $row = $form->addRow();
            $row->addLabel('activity', __('Activity'));
            $row->addTextField('activity')->maxLength(100)->required()->autocomplete($activities);
        
        $row = $form->addRow();
            $row->addAlert(__('If the activity name for awarding points already exists, ensure the text in the activity field is the same.'), "warning");

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

        echo "<br><p id='msg' style='color:blue;'></p>";
        
        //TODO: rewrite the code to maybe use a chained or something rather than this weird ajax...
        //TODO: this may require additions to the core src due to the nature of how it works, why do I get myself into these situations? :/
        ?>
        <script>
            $('#awardForm #categoryID').change(function(){
                $.ajax({
                    url: "./modules/House Points/category_points_ajax.php",
                    data: {
                        categoryID: $('#categoryID').val()
                    },
                    type: 'POST',
                    dataType: 'json',
                    success: function(data) {
                        // console.log(data);
                        var parent = $('#points').parent();
                        $('#points').detach().remove();

                        if (data !== null) {
                            var points = $('<select/>').attr('name', 'points').attr('id', 'points').attr('class', 'standardWidth');
                            $.each(data, function(value, label) {
                                points.append($("<option/>").attr("value", parseInt(value)).text(label));
                            });
                            parent.append(points);
                        } else {
                            var points = $('<input type="text" id="points" name="points" value="1" class="standardWidth" maxlength="4" />');
                            parent.append(points);
                        }
                    }
                });
            });

            $('#awardForm').change(function() {
                $('#msg').html('');
                $('#submit').prop('disabled', false);
            });
        </script>
        <?php
}
