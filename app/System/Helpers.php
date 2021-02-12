<?php

namespace App\System;

/**
 * Class Helpers
 * @package App
 */
class Helpers
{
    /**
     * Подсчитывает сколько нужно сделать отбивок, чтобы получить все посты за все время.
     * @param $posts_counter
     * @return int
     * @internal param $posts_num
     */
    public function countOffsets($posts_counter) {
        return round($posts_counter / 2500);
    }

    /**
     * @param $unix_time
     * @return array
     */
    public function dateFormatted($unix_time) {
        return [
            'hour' => date('G', $unix_time),
            'min' => date('i', $unix_time),
            'am_pm' => date('a', $unix_time),
            'week_day' => date('N', $unix_time),
            'day' => date('j', $unix_time),
            'month' => date('n', $unix_time),
            'year' => date('Y', $unix_time),
        ];
    }

    /**
     * @param int $seconds
     * @return array
     */
	public function formatSeconds(int $seconds)
	{
		$min = intval($seconds / 60);
		$sec = $seconds - ($min * 60);
		return [
		    'min' => $min,
            'sec' => $sec
        ];
    }
}
