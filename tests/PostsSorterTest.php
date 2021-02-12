<?php declare(strict_types=1);

use App\Async\AsyncProcesses;
use App\System\Helpers;
use App\Sorting\PostsSorter;
use PHPUnit\Framework\TestCase;

/**
 * Class PostsSorterTest
 */
class PostsSorterTest extends TestCase
{
    public function testMakePostsList()
    {
        $vk_api_result = new stdClass();
        $vk_api_result->id = [101, 99, 98, 97, 93];
        $vk_api_result->date = [1593767966, 1591864018, 1587474639, 1587210174, 1583049119];
        $vk_api_result->likes = [49, 18, 55, 3, 38];

        $async_processes = $this->getMockBuilder(AsyncProcesses::class)
            ->disableOriginalConstructor()
            ->getMock();
        $async_processes->method('getPostsFromVkApiAsync')
            ->willReturn([$vk_api_result]);

        $posts_sorter = new PostsSorter($async_processes, new Helpers());

        $this->assertEquals(
            [
                [
                    'likes' => 55,
                    'url' => 'https://vk.com/test_public?w=wall-123456789_98',
                    'timestamp' => 1587474639,
                ],
                [
                    'likes' => 49,
                    'url' => 'https://vk.com/test_public?w=wall-123456789_101',
                    'timestamp' => 1593767966,
                ],
                [
                    'likes' => 38,
                    'url' => 'https://vk.com/test_public?w=wall-123456789_93',
                    'timestamp' => 1583049119,
                ],
                [
                    'likes' => 18,
                    'url' => 'https://vk.com/test_public?w=wall-123456789_99',
                    'timestamp' => 1591864018,
                ],
                [
                    'likes' => 3,
                    'url' => 'https://vk.com/test_public?w=wall-123456789_97',
                    'timestamp' => 1587210174,
                ],
            ],
            $posts_sorter->makePostsList(123456789, 100, 'test_public')
        );
    }
}
