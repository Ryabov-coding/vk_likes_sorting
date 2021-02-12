<?php

namespace App\System;

use App\Exceptions\FrontendMessageException;
use App\Api\Vk;
use App\Sorting\PostsSorter;
use Exception;
use App\Db\PublicRequests;

/**
 * Class Controller
 */
class Controller
{
    /** @var Meta */
    private $meta;

    /** @var Vk */
    private $vk_api;

    /** @var PostsSorter */
    private $posts_sorter;

    /** @var PublicRequests */
    private $public_requests;

    /** @var Helpers */
    private $helpers;

    /** @var array */
    private $action_params = [];

	/**
	 * Controller constructor.
	 * @param Meta $meta
	 * @param Vk $vk_api
	 * @param PostsSorter $posts_sorter
	 * @param PublicRequests $public_requests
	 * @param Helpers $helpers
	 * @internal param Config $config
	 * @internal param $params
	 */
    public function __construct(
        Meta $meta,
        Vk $vk_api,
        PostsSorter $posts_sorter,
		PublicRequests $public_requests,
		Helpers $helpers
    )
    {
        $this->meta = $meta;
        $this->vk_api = $vk_api;
        $this->posts_sorter = $posts_sorter;
        $this->public_requests = $public_requests;
        $this->helpers = $helpers;
    }

    /**
     * Если паблик существует и не закрыт, возвращает его id и колличество постов
     */
    public function getPublicInfo()
    {
        $result = null;
        $public_name = $this->getActionParam('public_name');

        $this->meta->logUserAction('Запрос данных о паблике / ' . $public_name);
        $public_id = $this->vk_api->idByName($public_name);

        // Проверяем, что паблик существует и не закрыт
        if ($public_id && !$this->vk_api->isClosed($public_id)) {
            $result = [
                'public_id' => $public_id,
                'posts_counter' => $this->vk_api->postsCount($public_id)
            ];
        } else {
            throw new FrontendMessageException('Паблик не существует или закрыт');
        }

        return $result;
    }

    /**
     * Получить отсортированными все записи
     */
    public function getPosts()
    {
        $public_id = (integer) $this->getActionParam('public_id');
        $posts_counter = $this->vk_api->postsCount($public_id);
        $public_info = $this->vk_api->groupInfo($public_id);

        $this->meta->logUserAction('Запрос постов паблика / ' . $public_id);

		$start_time = time();

        $all_posts = $this->posts_sorter->makePostsList(
            $public_id,
            $posts_counter,
            $public_info->response[0]->screen_name
        );

        if ($posts_counter > 0 && empty($all_posts)) {
            throw new Exception('Не удалось получить посты паблика');
        }

        $this->public_requests->saveRequest(
        	$public_id,
			$public_info->response[0]->screen_name,
			$public_info->response[0]->name,
			time() - $start_time + 1 // Плюс секунду за первый запрос с проверкой существования паблика
		);

        $this->meta->saveToSession($public_id,'all_posts', $all_posts);

        return [
            'name' => $public_info->response[0]->name,
            'avatar_link' => $public_info->response[0]->photo_200,
            'posts_counter' => $posts_counter,
            'likes_counter' => $this->posts_sorter->getTotalLikes(),
            'posts' => $this->posts_sorter->formatPostsTime(
                array_slice($all_posts, 0, $this->meta->getConfigParam('posts_per_page'))
            ),
        ];
    }

    /**
     * Показать ещё записи
     */
    public function morePosts()
    {
        $public_id = $this->getActionParam('public_id');
        $this->meta->logUserAction('Запрос на получение дополнительных постов паблика / ' . $public_id);

        $result_posts = $this->meta->getFromSession($public_id, 'all_posts');
        $filters = json_decode($this->getActionParam('filters'), true);

        // Если есть активные фильтры, фильтруем
        if (!empty($filters['time']) || !empty($filters['weekday']) || !empty($filters['date'])) {
            $result_posts = $this->posts_sorter->filterPosts($result_posts, $filters)['posts'];
        }

        return [
            'posts' => $this->posts_sorter->formatPostsTime(
                array_slice(
                    $result_posts,
                    $this->getActionParam('offset'),
                    $this->meta->getConfigParam('posts_per_page')
                )
            ),
        ];
    }

    /**
     * Вернуть список в обратном порядке
     */
    public function upendPosts()
    {
        $public_id = $this->getActionParam('public_id');
        $filters = $this->getActionParam('filters');

        $this->meta->logUserAction('Запрос на переворот постов паблика / ' . $public_id);

        $all_posts = $this->meta->getFromSession($public_id, 'all_posts');
        $result_posts = array_reverse($all_posts);
        $this->meta->saveToSession($public_id,'all_posts', $result_posts);

        // Если есть активные фильтры, фильтруем
        if (!empty($filters['time']) || !empty($filters['weekday']) || !empty($filters['date'])) {
            $result_posts = $this->posts_sorter->filterPosts($result_posts, $filters)['posts'];
        }

        return [
            'posts' => $this->posts_sorter->formatPostsTime(
                array_slice(
                    $result_posts,
                    0,
                    $this->meta->getConfigParam('posts_per_page')
                )
            ),
        ];
    }

    /**
     * Отфильтровать по времени, дню недели или дате
     */
    public function filterPosts()
    {
        $public_id = $this->getActionParam('public_id');

        $this->meta->logUserAction('Запрос на фильтрацию постов паблика / ' . $public_id);

        $filtered = $this->posts_sorter->filterPosts(
            $this->meta->getFromSession($public_id, 'all_posts'),
            json_decode($this->getActionParam('filters'), true)
        );

        return [
            'posts' => $this->posts_sorter->formatPostsTime(
                array_slice(
                    $filtered['posts'],
                    0,
                    $this->meta->getConfigParam('posts_per_page')
                )
            ),
            'posts_counter' => $filtered['posts_counter'],
            'likes_counter' => $filtered['likes_counter'],
        ];
    }

    public function getLastRequests()
	{
		$result = [];
		foreach ($this->public_requests->getLastPublicRequests() as $request) {
			$result[] = [
				'id' => $request['public_id'],
				'title' => $request['public_name'],
				'spend_time' => $this->helpers->formatSeconds($request['spend_time']),
				'link' => 'https://vk.com/public' . $request['public_id'],
			];
		}

		return $result;
	}

    /**
     * @param array $params
     */
    public function setActionParams(array $params)
    {
        $this->action_params = $params;
    }

    /**
     * @param $name
     * @param bool $necessarily
     * @return mixed
     * @throws Exception
     */
    private function getActionParam($name, $necessarily = true) {
        if ($necessarily && !isset($this->action_params[$name])) {
            throw new Exception('Не передан параметр ' . $name);
        }
        return $this->action_params[$name];
    }
}
