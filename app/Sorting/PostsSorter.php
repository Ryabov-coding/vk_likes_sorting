<?php

namespace App\Sorting;

use App\Async\AsyncProcesses;
use App\System\Helpers;

/**
 * Class PostsSorter
 */
class PostsSorter {

    /** @var AsyncProcesses */
    private $async_processes;

    /** @var Helpers */
    private $helpers;

    /** @var integer */
    private $total_likes = 0;

    /**
     * PostsSorter constructor.
     * @param AsyncProcesses $async_processes
     * @param Helpers $helpers
     * @internal param Api $vk_api
     */
    public function __construct(AsyncProcesses $async_processes, Helpers $helpers) {
        $this->async_processes = $async_processes;
        $this->helpers = $helpers;
    }

    /**
     * @return int
     */
    public function getTotalLikes()
    {
        return $this->total_likes;
    }

    /**
     * Создает список постов
     * @param $public_id
     * @param $posts_counter
     * @param $public_scr_name
     * @return array
     * @throws \Exception
     * @internal param $group_id
     */
    public function makePostsList($public_id, $posts_counter, $public_scr_name) {
        $result = [];
        $vk_api_response = $this->async_processes->getPostsFromVkApiAsync($public_id, $posts_counter);

        foreach ($vk_api_response as $posts_raw_std) {
            for ($a = 0; $a < count($posts_raw_std->id); $a++) {
                $result[] = [
                    'likes' => $posts_raw_std->likes[$a],
                    'url' => 'https://vk.com/' . $public_scr_name . '?w=wall-' . $public_id . '_' . $posts_raw_std->id[$a],
                    'timestamp' => $posts_raw_std->date[$a],
                ];
                $this->total_likes += $posts_raw_std->likes[$a];
            }
        }

        usort($result, function ($a, $b) {
            return $a['likes'] < $b['likes'];
        });

        return $result;
    }

    /**
     * Обрабатывает массив постов, формитируя время в обычный вид
     * @param $posts
     * @return array
     */
    public function formatPostsTime(array $posts)
    {
        foreach ($posts as &$post) {
            $post['datetime'] = $this->helpers->dateFormatted($post['timestamp']);
        }
        return $posts;
    }

    /**
     * Производит фильтрацию записей. Проходит циклом по списку и проверяет каждую запись по тем
     * фильтрам, которые были переданы в массиве фильтров
     * @param $posts
     * @param $filters
     * @return array
     * @internal param $list
     */
    public function filterPosts(array $posts, array $filters){
        $result = [
            'posts' => [],
            'posts_counter' => 0,
            'likes_counter' => 0,
        ];

        foreach ($posts as $key => $post) {
            // фильтры по дате
            if (!empty($filters['date']['from']) && $post['timestamp'] < strtotime($filters['date']['from'])) {
                continue;
            }
            if (!empty($filters['date']['to']) && $post['timestamp'] > strtotime($filters['date']['to'])) {
                continue;
            }
            
            // фильтр по дню недели
            $weekday = date('N', $post['timestamp']);
            if (!empty($filters['weekday']) && !in_array($weekday, $filters['weekday'])) {
                continue;
            }
            
            // фильтр по часу
            $hours = date('G', $post['timestamp']);
            if (!empty($filters['time']) && !in_array($hours, $filters['time'])) {
                continue;
            }
            
            // если не один фильтр не сработал, то пост записывается в новый массив
            array_push($result['posts'], $post);
            $result['posts_counter']++;
            $result['likes_counter'] += $post['likes'];
        }
        
        return $result;
    }
}