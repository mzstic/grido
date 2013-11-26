<?php

/**
 * Test: Grid.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid,
    Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

class GridTest extends \Tester\TestCase
{
    function testSetModel()
    {
        $grid = new Grid;
        $grid->setModel(mock('Grido\DataSources\IDataSource'));
        Assert::type('Grido\DataSources\IDataSource', $grid->model);

        $grid->setModel(mock('Grido\DataSources\IDataSource'), TRUE);
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(new \DibiFluent(mock('\DibiConnection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Nette\Database\Table\Selection'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(mock('\Doctrine\ORM\QueryBuilder'));
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel([]);
        Assert::type('Grido\DataSources\Model', $grid->model);

        $grid->setModel(new \DibiFluent(mock('\DibiConnection')));
        Assert::type('Grido\DataSources\Model', $grid->model);

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'));
        }, 'InvalidArgumentException', 'Model must be implemented \Grido\DataSources\IDataSource.');

        Assert::exception(function() use ($grid) {
            $grid->setModel(mock('BAD'), TRUE);
        }, 'InvalidArgumentException', 'Model must be implemented \Grido\DataSources\IDataSource.');
    }

    function testSetPropertyAccessor()
    {
        $grid = new Grid;

        $expected = 'Grido\PropertyAccessors\IPropertyAccessor';
        $grid->setPropertyAccessor(mock($expected));
        Assert::type($expected, $grid->propertyAccessor);

        Assert::error(function() use ($grid) {
            $grid->setPropertyAccessor('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetDefaultPerPage()
    {
        $grid = new Grid;
        $data = [[], [], [], []];
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);
        Assert::same(20, $grid->defaultPerPage);

        $defaultPerPage = 2;
        $perPageList = $grid->perPageList;
        $perPageList[] = $defaultPerPage;
        sort($perPageList);

        $grid->setDefaultPerPage((string) $defaultPerPage);
        Assert::same($defaultPerPage, $grid->defaultPerPage);
        Assert::same($perPageList, $grid->perPageList);
        Assert::same($defaultPerPage, count($grid->data));

        $grid = new Grid;
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');
        $grid->setDefaultPerPage(2);
        $grid->perPage = 10;
        Assert::same(count($data), count($grid->data));

        Assert::error(function() {
            $grid = new Grid;
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
            $grid->perPage = 1;
            $grid->data;
        }, E_USER_NOTICE, "The number '1' of items per page is out of range.");
    }

    function testSetDefaultFilter()
    {
        $grid = new Grid;

        Assert::error(function() use ($grid) {
            $grid->setDefaultFilter('');
        }, E_RECOVERABLE_ERROR);

        $data = [
            ['A' => 'A1', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A3', 'B' => 'B3'],
        ];
        $grid->setModel($data);
        $grid->addColumnText('column', 'Column');
        $grid->addFilterText('A', 'Column');
        $defaultFilter = ['A' => 'A2'];
        $grid->setDefaultFilter($defaultFilter);

        Assert::same($defaultFilter, $grid->defaultFilter);
        Assert::same([['A' => 'A2', 'B' => 'B2']], $grid->data);
        Assert::same('A2', $grid['form'][Filter::ID]['A']->value);

        Assert::error(function() use ($defaultFilter) {
            $grid = new Grid;
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
            $grid->setDefaultFilter($defaultFilter);
            $grid->getData();
        }, E_USER_NOTICE, "Filter with name 'A' does not exist.");
    }

    function testSetDefaultSort()
    {
        $grid = new Grid;
        $grid->setDefaultSort(['a' => 'ASC', 'b' => 'desc', 'c' => 'Asc', 'd' => Column::ORDER_DESC]);
        Assert::same(['a' => Column::ORDER_ASC, 'b' => Column::ORDER_DESC, 'c' => Column::ORDER_ASC, 'd' => Column::ORDER_DESC], $grid->defaultSort);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(['a' => 'up']);
        }, 'InvalidArgumentException', "Dir 'up' for column 'a' is not allowed.");

        $grid = new Grid;
        $data = [
            ['A' => 'A1', 'B' => 'B3'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A3', 'B' => 'B1'],
        ];
        $grid->setModel($data);
        $grid->addColumnText('B', 'B');
        $grid->setDefaultSort(['B' => 'asc']);
        $grid2 = clone $grid;

        $expected = [
            ['A' => 'A3', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A1', 'B' => 'B3'],
        ];
        Assert::same($expected, $grid->data);

        $grid2->sort['B'] = Column::ORDER_DESC;
        Assert::same($data, $grid2->data);

        $grid = new Grid;
        $grid->setModel($data);
        $grid->setDefaultSort(['A' => 'desc']);

        $A = [];
        foreach ($data as $key => $row) {
            $A[$key] = $row['A'];
        }
        array_multisort($A, SORT_DESC, $data);
        Assert::same($data, $grid->data);

        Assert::exception(function() use ($grid) {
            $grid->setDefaultSort(['A' => 'up']);
        }, 'InvalidArgumentException', "Dir 'up' for column 'A' is not allowed.");
    }

    function testSetPerPageList()
    {
        $grid = new Grid;

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);

        $grid->addFilterText('test', 'Test');

        $a = [10, 20];
        $grid->setPerPageList($a);
        Assert::same($a, $grid->perPageList);
        Assert::same(array_combine($a, $a), $grid['form']['count']->items);
    }

    function testSetTranslator()
    {
        $grid = new Grid;

        $translator = '\Nette\Localization\ITranslator';
        $grid->setTranslator(mock($translator));
        Assert::type($translator, $grid->translator);

        Assert::error(function() use ($grid) {
            $grid->setTranslator('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetFilterRenderType()
    {
        $grid = new Grid;

        $type = Filter::RENDER_INNER;
        $grid->setFilterRenderType($type);
        Assert::same($type, $grid->filterRenderType);

        $type = Filter::RENDER_OUTER;
        $grid->setFilterRenderType($type);
        Assert::same($type, $grid->filterRenderType);

        $grid->setFilterRenderType('OUTER');
        Assert::same($type, $grid->filterRenderType);

        Assert::exception(function() use ($grid) {
            $grid->setFilterRenderType('INNERR');
        }, 'InvalidArgumentException', 'Type must be Filter::RENDER_INNER or Filter::RENDER_OUTER.');
    }

    function testSetPaginator()
    {
        $grid = new Grid;

        $paginator = '\Grido\Components\Paginator';
        $grid->setPaginator(mock($paginator));
        Assert::type($paginator, $grid->paginator);

        Assert::error(function() use ($grid) {
            $grid->setPaginator('');
        }, E_RECOVERABLE_ERROR);
    }

    function testSetPrimaryKey()
    {
        $grid = new Grid;
        $key = 'id';
        $grid->setPrimaryKey($key);
        Assert::same($key, $grid->primaryKey);
    }

    function testSetTemplateFile()
    {
        $grid = new Grid;
        $template = __FILE__;
        $grid->setTemplateFile($template);
        Assert::same($template, $grid->template->getFile());
    }

    function testSetRememberState()
    {
        $grid = new Grid;
        $grid->setRememberState(1);
        Assert::true($grid->rememberState);
    }

    function testSetRowCallback()
    {
        $grid = new Grid;

        $rowCallback = [];
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);

        $testRow = ['id' => 1, 'key' => 'value'];
        $rowCallback = function($row, \Nette\Utils\Html $tr) use ($testRow) {
            Assert::same($testRow, $row);
        };
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);
        $grid->getRowPrototype($testRow);

        $rowCallback = mock('\Nette\Utils\Callback');
        $grid->setRowCallback($rowCallback);
        Assert::same($rowCallback, $grid->rowCallback);
    }

    function testSetClientSideOptions()
    {
        $grid = new Grid;
        $options = ['key' => 'value'];
        $grid->setClientSideOptions($options);
        Assert::same($grid->tablePrototype->data['grido-options'], json_encode($options));
    }

    /**********************************************************************************************/

    function testGetDefaultPerPage()
    {
        $grid = new Grid;

        //test defaults
        Assert::same([10, 20, 30, 50, 100], $grid->perPageList);
        Assert::same(20, $grid->defaultPerPage);

        $grid->setPerPageList([2, 4, 6]);
        Assert::same(2, $grid->defaultPerPage);
    }

    function testGetActualFilter()
    {
        $grid = new Grid;
        $filter = ['a' => 'A', 'b' => 'B'];
        $defaultFilter = ['c' => 'C', 'd' => 'D'];

        Assert::same([], $grid->getActualFilter());

        $grid->defaultFilter = $defaultFilter;
        Assert::same($defaultFilter, $grid->getActualFilter());
        Assert::same($defaultFilter, $grid->getActualFilter('undefined'));
        Assert::same('D', $grid->getActualFilter('d'));

        $grid->filter = $filter;
        Assert::same($filter, $grid->getActualFilter());
        Assert::same($filter, $grid->getActualFilter('undefined'));
        Assert::same('B', $grid->getActualFilter('b'));
    }

    function testGetFilterRenderType()
    {
        $grid = new Grid;
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addActionHref('action', 'Action');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        $grid->addColumnText('yyy', 'Column');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addColumnText('xxx', 'Column');
        Assert::same(Filter::RENDER_OUTER, $grid->filterRenderType);

        $grid = new Grid;
        $grid->addFilterText('xxx', 'Filter');
        $grid->addActionHref('action', 'Action');
        $grid->addColumnText('xxx', 'Column');
        Assert::same(Filter::RENDER_INNER, $grid->filterRenderType);
    }

    function testGetTablePrototype()
    {
        $grid = new Grid;
        $table = $grid->tablePrototype;

        $table->class[] = 'test';
        Assert::same('<table class="table table-striped table-hover test"></table>', (string) $table);
    }

    /**********************************************************************************************/

    function testHandlePage()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setDefaultPerPage(2);
            $grid->addColumnText('column', 'Column');
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B3'],
                ['A' => 'A2', 'B' => 'B2'],
                ['A' => 'A3', 'B' => 'B1'],
            ]);
            $grid->getData();
        });

        Helper::request(['grid-page' => 2, 'do' => 'grid-page']);
        Assert::same([['A' => 'A3', 'B' => 'B1']], Helper::$grid->data);
    }

    function testHandleSort()
    {
        Helper::grid(function(Grid $grid) {
            $grid->addColumnText('column', 'Column')->setSortable();
        });

        $sorting = ['column' => Column::ORDER_ASC];
        Helper::request(['grid-page' => 2, 'grid-sort' => $sorting, 'do' => 'grid-sort']);
        Assert::same($sorting, Helper::$grid->sort);
        Assert::same(1, Helper::$grid->page);

        Helper::grid(function(Grid $grid) {
            $grid->setDefaultPerPage(2);
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B3'],
                ['A' => 'A2', 'B' => 'B2'],
                ['A' => 'A3', 'B' => 'B1'],
            ]);
            $grid->addColumnText('A', 'A');
            $grid->addColumnText('B', 'B')->setSortable();
        });

        Helper::request(['grid-page' => 2, 'grid-sort' => ['B' => Column::ORDER_ASC], 'do' => 'grid-sort']);

        Assert::same(1, Helper::$grid->page); //test reset page after sorting
        Assert::same([
            ['A' => 'A3', 'B' => 'B1'],
            ['A' => 'A2', 'B' => 'B2'],
        ], Helper::$grid->data);

        //applySorting()
        Helper::request(['grid-sort' => ['B' => 'UP'], 'do' => 'grid-sort']);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Dir 'UP' is not allowed.");

        Helper::request(['grid-sort' => ['C' => Column::ORDER_ASC], 'do' => 'grid-sort']);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'C' does not exist.");

        Helper::request(['grid-sort' => ['A' => Column::ORDER_ASC], 'do' => 'grid-sort']);
        Assert::error(function(){
            Helper::$grid->data;
        }, 'E_USER_NOTICE', "Column with name 'A' is not sortable.");
    }

    function testHandleFilter()
    {
        $defaultFilter = ['filterB' => 'test'];
        Helper::grid(function(Grid $grid) use ($defaultFilter) {
            $grid->setModel([]);
            $grid->setDefaultFilter($defaultFilter);
            $grid->addFilterText('filter', 'Filter');
            $grid->addFilterText('filterB', 'FilterB');
        });

        $params = ['grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => ['search' => 'Search']];

        $filter = ['filter' => 'test'] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = ['filter' => ''] + $defaultFilter;
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($defaultFilter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = ['filter' => '', 'filterB' => 'test'];
        Helper::request($params + [Filter::ID => $filter]);
        unset($filter['filter']);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $filter = ['filter' => 'test', 'filterB' => ''];
        Helper::request($params + [Filter::ID => $filter]);
        Assert::same($filter, Helper::$grid->filter);
        Assert::same(1, Helper::$grid->page);

        $data = [
            ['A' => 'A1', 'B' => 'B3'],
            ['A' => 'A2', 'B' => 'B2'],
            ['A' => 'A22','B' => 'B22'],
            ['A' => 'A3', 'B' => 'B1'],
        ];

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setDefaultPerPage(1);
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('B', 'B');
        });

        Helper::request([
            'do' => 'grid-form-submit',
            'grid-page' => 2,
            Filter::ID => ['B' => 'B2'],
            Grid::BUTTONS => ['search' => 'Search'],
        ]);

        Assert::same(1, Helper::$grid->page); //test reset page after filtering

        $expected = [
            1 => ['A' => 'A2', 'B' => 'B2'],
            2 => ['A' => 'A22', 'B' => 'B22'],
        ];
        Assert::same($expected, Helper::$grid->getData(FALSE));

        Helper::grid(function(Grid $grid) use ($data) {
            $grid->setModel($data);
            $grid->addColumnText('column', 'Column');
            $grid->addFilterText('A', 'A');
            $grid->addFilterText('B', 'B')
                ->setDefaultValue('B2');
        });

        Helper::request([
            'do' => 'grid-form-submit',
            'grid-page' => 1,
            Filter::ID => ['A' => '', 'B' => ''],
            Grid::BUTTONS => ['search' => 'Search'],
        ]);

        Assert::same($data, Helper::$grid->getData(FALSE));
        Assert::same(['B' => ''], Helper::$grid->filter);

        Assert::error(function() use ($data) {
            $grid = new Grid;
            $grid->addColumnText('column', 'Column');
            $grid->setModel($data);
            $grid->addFilterText('A', 'A');
            $grid->filter['B'] = 'B2';
            $grid->data;
        }, E_USER_NOTICE, "Filter with name 'B' does not exist.");
    }

    function testHandleReset()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setPerPageList([1, 2]);
            $grid->setDefaultPerPage(1);
            $grid->setModel([
                ['A' => 'A1', 'B' => 'B4'],
                ['A' => 'A2', 'B' => 'B3'],
                ['A' => 'A3', 'B' => 'B2'],
                ['A' => 'A4', 'B' => 'B1'],
            ]);

            $grid->addColumnText('A', 'A')->setSortable();
            $grid->addFilterText('B', 'B');

            $params = [
                'sort' => ['A' => Column::ORDER_ASC],
                'filter' => ['B' => 'B2'],
                'perPage' => 2,
                'page' => 2

            ];
            $grid->loadState($params);
        });

        Helper::request(['do' => 'grid-form-submit', Grid::BUTTONS => ['reset' => 'Reset']]);
        Assert::same([], Helper::$grid->sort);
        Assert::same([], Helper::$grid->filter);
        Assert::null(Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }

    function testHandlePerPage()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel([]);
            $grid->addColumnText('column', 'Column');
        });

        $perPage = 10;
        Helper::request(['count' => $perPage, 'grid-page' => 2, 'do' => 'grid-form-submit', Grid::BUTTONS => ['perPage' => 'Items per page']]);
        Assert::same($perPage, Helper::$grid->perPage);
        Assert::same(1, Helper::$grid->page);
    }

    /**********************************************************************************************/

    function testOnFetchDataCallback()
    {
        $grid = new Grid;
        $testData = ['id' => 1, 'column' => 'value'];
        $grid->setModel($testData);
        $grid->onFetchData[] = function(Grid $grid) use ($testData) {
            Assert::same($testData, $grid->data);
        };
    }
}

run(__FILE__);
