<?php

namespace Joesama\StressPest;

use Illuminate\Config\Repository;

trait StressConfig
{
    private ?Repository $configRepo = null;

    private string $stressFile;

    /**
     * @return $this
     */
    public function envConfig(string $file = 'stress'): static
    {
        $this->configRepo = app()->config;
        $this->stressFile = $file;

        return $this;
    }

    /**
     * @param int $duration
     * @return StressConfig
     */
    public function setDuration(int $duration): static
    {
        $this->configRepo->set($this->stressFile.'.duration', $duration);

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->configs('duration');
    }

    /**
     * @param int $concurrent
     * @return StressConfig
     */
    public function setConcurrent(int $concurrent): static
    {
        $this->configRepo->set($this->stressFile.'.concurrent', $concurrent);

        return $this;
    }

    /**
     * @return int
     */
    public function getConcurrent(): int
    {
        return $this->configs('concurrent');
    }

    /**
     * @param string|null $params
     * @return mixed
     */
    public function configs(?string $params = null): mixed
    {
        return $this->configRepo->get($this->stressFile.($params ? '.'.$params : ''));
    }
}