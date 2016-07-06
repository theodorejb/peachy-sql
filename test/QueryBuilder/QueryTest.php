<?php

namespace PeachySQL\QueryBuilder;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    private $propertyMap = [
        'name' => 'UserName',
        'client' => [
            'id' => 'ClientID',
            'name' => 'ClientName',
            'isDisabled' => 'isDisabled',
        ],
        'group' => [
            'type' => [
                'id' => 'GroupTypeID',
                'name' => 'GroupName',
            ],
        ],
    ];

    public function testPropertiesToColumns()
    {
        $q = [
            'name' => 'testUser',
            'client' => [
                'name' => [
                    'lk' => 'test%',
                    'nl' => 'test1%',
                ],
                'isDisabled' => '0',
            ],
            'group' => [
                'type' => [
                    'name' => ['group 1', 'group 2'],
                ],
            ],
        ];

        $expected = [
            'UserName' => 'testUser',
            'ClientName' => [
                'lk' => 'test%',
                'nl' => 'test1%',
            ],
            'isDisabled' => '0',
            'GroupName' => ['group 1', 'group 2'],
        ];

        $this->assertSame($expected, Query::propertiesToColumns($this->propertyMap, $q));

        $sort = [
            'client' => [
                'name' => 'asc',
                'isDisabled' => 'desc',
            ],
        ];

        $sortExpectation = ['ClientName' => 'asc', 'isDisabled' => 'desc'];
        $this->assertSame($sortExpectation, Query::propertiesToColumns($this->propertyMap, $sort));
        $this->assertSame([], Query::propertiesToColumns($this->propertyMap, []));
    }

    public function testInvalidPropertiesToColumes()
    {
        try {
            $badQ = [
                'client' => [
                    'lk' => 'foo%',
                ],
            ];

            Query::propertiesToColumns($this->propertyMap, $badQ);
            $this->fail('Failed to throw exception for invalid property');
        } catch (\Exception $e) {
            $this->assertSame('Invalid property lk', $e->getMessage());
        }
    }
}
