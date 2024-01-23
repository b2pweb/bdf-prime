<?php

namespace Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\Platform\SqlUtil;
use PHPUnit\Framework\TestCase;

class SqlUtilTest extends TestCase
{
    public function test_tables()
    {
        $this->assertEquals([], SqlUtil::tables('SELECT 1 + 1'));
        $this->assertEquals(['DUAL' => 'DUAL'], SqlUtil::tables('SELECT 1 + 1 FROM DUAL'));
        $this->assertEquals(
            [
                'sales' => 'sales',
                'sub' => 'sub',
                'main' => 'products',
            ],
            SqlUtil::tables(<<<SQL
                SELECT 
                    main.id, 
                    main.name, 
                    sub.total_sales
                FROM 
                    (SELECT 
                        product_id, 
                        SUM(quantity) as total_sales 
                     FROM 
                        sales 
                     GROUP BY 
                        product_id) AS sub
                JOIN 
                    products AS main 
                ON 
                    main.id = sub.product_id
                WHERE 
                    main.price > 1000
                ORDER BY 
                    sub.total_sales DESC
                SQL
            )
        );
        $this->assertEquals(
            [
                'a' => 'table1',
                'table2' => 'table2',
                'c' => 'table3',
            ],
            SqlUtil::tables(<<<SQL
                SELECT
                    a.column1,
                    b.column2,
                    c.column3
                FROM
                    table1 AS a,
                    table2,
                    table3 c
                WHERE
                    a.id = b.id
                AND
                    a.id = c.id;
                SQL
            )
        );
    }
}
