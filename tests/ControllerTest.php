<?php declare(strict_types=1);

use App\Api\Vk;
use App\Async\AsyncProcesses;
use App\System\Controller;
use App\Exceptions\FrontendMessageException;
use App\System\Helpers;
use App\System\Meta;
use App\Sorting\PostsSorter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 */
class ControllerTest extends TestCase
{
    /** @var MockObject */
    protected $meta;

    /** @var MockObject */
    protected $api;

    /** @var MockObject */
    protected $posts_sorter;

    /** @var MockObject */
    protected $helpers;

    /** @var MockObject */
    protected $async_processes;

    /** @var MockObject */
    protected $controller;

    public function setUp():void
    {
        $this->meta = $this->getMockBuilder(Meta::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->api = $this->getMockBuilder(Vk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->posts_sorter = $this->getMockBuilder(PostsSorter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helpers = $this->getMockBuilder(Helpers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->async_processes = $this->getMockBuilder(AsyncProcesses::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new Controller($this->meta, $this->api, $this->posts_sorter);
    }

    public function testLackOfActionParam()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Не передан параметр public_name');
        $this->controller->getPublicInfo();
    }

    public function testGetPublicInfoResult()
    {
        $this->api
            ->method('idByName')
            ->willReturn(123456789);
        $this->api
            ->method('isClosed')
            ->willReturn(false);
        $this->api
            ->method('postsCount')
            ->willReturn(1000);

        $controller = new Controller(
            $this->meta,
            $this->api,
            $this->posts_sorter
        );
        $controller->setActionParams(['public_name' => 'test']);

        $this->assertEquals([
            'public_id' => 123456789,
            'posts_counter' => 1000
        ], $controller->getPublicInfo());
    }

    public function testGetPublicInfoClosedPublic()
    {
        $this->expectException(FrontendMessageException::class);

        $this->api
            ->method('idByName')
            ->willReturn(null);
        $this->api
            ->method('isClosed')
            ->willReturn(true);

        $controller = new Controller(
            $this->meta,
            $this->api,
            $this->posts_sorter
        );
        $controller->setActionParams(['public_name' => 'test']);

        $controller->getPublicInfo();
    }

    public function testFilterPostsResult()
    {
        date_default_timezone_set('Europe/Moscow');
        $posts_sorter = new PostsSorter($this->async_processes, new Helpers());

        $this->meta->method('getFromSession')->willReturn(
            [
                [
                    'likes' => 196,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_71',
                    'timestamp' => 1573481841,
                ],
                [
                    'likes' => 114,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_28',
                    'timestamp' => 1571127001,
                ],
                [
                    'likes' => 55,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_98',
                    'timestamp' => 1587474639,
                ],
                [
                    'likes' => 50,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_24',
                    'timestamp' => 1570559705,
                ],
                [
                    'likes' => 49,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_101',
                    'timestamp' => 1593767966,
                ],
            ]
        );
        $this->meta->method('getConfigParam')
            ->willReturnCallback(function ($param) {
                return $param == 'posts_per_page'
                    ? 100
                    : null;
            });

        $controller = new Controller(
            $this->meta,
            $this->api,
            $posts_sorter
        );
        $controller->setActionParams([
            'public_id' => 123456789,
            'filters' => '{"time":null,"weekday":["2"],"date":null}'
        ]);

        $this->assertEquals([
            'posts' => [
                [
                    'likes' => 114,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_28',
                    'timestamp' => 1571127001,
                    'time' => '11:10',
                    'week_day' => 'Вт.',
                    'day_month' => '15 Окт.',
                    'year' => '2019'
                ],
                [
                    'likes' => 55,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_98',
                    'timestamp' => 1587474639,
                    'time' => '16:10',
                    'week_day' => 'Вт.',
                    'day_month' => '21 Апр.',
                    'year' => '2020'
                ],
                [
                    'likes' => 50,
                    'url' => 'https://vk.com/altruistdiary?w=wall-186473262_24',
                    'timestamp' => 1570559705,
                    'time' => '21:35',
                    'week_day' => 'Вт.',
                    'day_month' => '08 Окт.',
                    'year' => '2019'
                ],
            ],
            'posts_counter' => 3,
            'likes_counter' => 219
        ], $controller->filterPosts());
    }
}