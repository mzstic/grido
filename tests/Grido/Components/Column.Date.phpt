<?php

/**
 * Test: Date column.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Date;

require_once __DIR__ . '/../bootstrap.php';

test(function() {
    $grid = new Grid;
    $column = $grid->addColumnDate('column', 'Column')->setReplacement([
        NULL => 'NULL', '2012-12-21' => 'The End of the World'
    ]);

    Assert::same('NULL', $column->render(['column' => NULL]));
    Assert::same('NULL', $column->renderExport(['column' => NULL]));
    Assert::same('The End of the World', $column->render(['column' => '2012-12-21']));
    Assert::same('The End of the World', $column->renderExport(['column' => '2012-12-21']));
    Assert::same('01.01.1970', $column->render(['column' => '<script>alert("XSS")</script>']));
    Assert::same('01.01.1970', $column->renderExport(['column' => '<script>alert("XSS")</script>']));

    $input = '2012-12-12';
    $output = '12.12.2012';
    Assert::same($output, $column->render(['column' => $input]));
    Assert::same($output, $column->render(['column' => new DateTime($input)]));
    Assert::same($output, $column->render(['column' => strtotime($input)]));
    Assert::same($output, $column->renderExport(['column' => $input]));
    Assert::same($output, $column->renderExport(['column' => new DateTime($input)]));
    Assert::same($output, $column->renderExport(['column' => strtotime($input)]));

    $column->setDateFormat(Date::FORMAT_TEXT);
    $output = '12 Dec 2012';
    Assert::same($output, $column->render(['column' => $input]));
    Assert::same($output, $column->render(['column' => new DateTime($input)]));
    Assert::same($output, $column->renderExport(['column' => $input]));
    Assert::same($output, $column->renderExport(['column' => new DateTime($input)]));

    $column->setDateFormat(Date::FORMAT_DATETIME);
    $output = '12.12.2012 00:00:00';
    Assert::same($output, $column->render(['column' => $input]));
    Assert::same($output, $column->render(['column' => new DateTime($input)]));
    Assert::same($output, $column->renderExport(['column' => $input]));
    Assert::same($output, $column->renderExport(['column' => new DateTime($input)]));
});
