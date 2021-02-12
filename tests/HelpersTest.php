<?php declare(strict_types=1);

use App\System\Helpers;
use PHPUnit\Framework\TestCase;

/**
 * Class HelpersTest
 */
class HelpersTest extends TestCase
{
    public function testDateFormatted()
    {
        date_default_timezone_set('Europe/Moscow');
        $this->assertEquals(
            [
                'time' => '17:17',
                'week_day' => 'Пн.',
                'day_month' => '11 Ноя.',
                'year' => '2019'
            ],
            (new Helpers())->dateFormatted(1573481841)
        );
    }
}
