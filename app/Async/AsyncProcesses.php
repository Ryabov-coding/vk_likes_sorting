<?php

namespace App\Async;

use App\System\Helpers;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class AsyncProcesses
 * @package App\Async
 */
class AsyncProcesses
{
    /** @var Helpers */
    private $helpers;

    /**
     * AsyncProcesses constructor.
     * @param Helpers $helpers
     */
    public function __construct(Helpers $helpers)
    {
        $this->helpers = $helpers;
    }

    /**
     * Для получения постов из АПИ Вконтакта асинхронно, так как за раз можно получить не больше 2500 постов
     * @param $public_id
     * @param $posts_counter
     * @return array
     * @throws \Exception
     */
    public function getPostsFromVkApiAsync($public_id, $posts_counter)
    {
        $result = [];
        $processes = [];

        try {
            // Запускаем все процессы
            for ($a = 0; $a <= $this->helpers->countOffsets($posts_counter); $a++) {
                $offset = $a * 2500;
                $processes['process_' . ($a + 1)] = new Process([
                    'php', CHILD_PROCESSES_DIR . '/PublicPostsApiRequest.php',
                    '--public_id', $public_id, '--offset', $offset,
                ]);
                $processes['process_' . ($a + 1)]->start();
                usleep(400000);
            }

            // Ждем завершения всех процессов
            do {
                $processes_finished = true;
                foreach ($processes as $process) {
                    $processes_finished = $processes_finished && !$process->isRunning();
                }
                usleep(100000);
            } while (!$processes_finished);

            // Получаем результат всех процессов
            foreach ($processes as $process) {
                $result = array_merge($result, json_decode($process->getOutput()));
            }
        } catch (ProcessFailedException $e) {
            throw new \Exception($e->getMessage());
        }

        return $result;
    }
}