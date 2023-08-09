<?php

namespace Triibo\Mautic;

use GrahamCampbell\Manager\AbstractManager;
use Illuminate\Contracts\Config\Repository;
use Triibo\Mautic\Factories\MauticFactory;
use Triibo\Mautic\Models\MauticConsumer;

class Mautic extends AbstractManager
{
    /**
     * The factory instance.
     *
     * @var \Mautic\Factory
     */
    protected $factory;

    /**
     * Create a new Mautic manager instance.
     *
     *
     * @return void
     */
    public function __construct(Repository $config, MauticFactory $factory)
    {
        parent::__construct($config);

        $this->factory = $factory;
    }

    /**
     * Create the connection instance.
     *
     *
     * @return mixed
     */
    protected function createConnection(array $config): object
    {
        return $this->factory->make($config);
    }

    /**
     * Get the configuration name.
     */
    protected function getConfigName(): string
    {
        return 'mautic';
    }

    /**
     * Get the factory instance.
     *
     * @return \Mautic\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param  null  $method
     * @param  null  $endpoints
     * @param  null  $body
     * @return mixed
     */
    public function request($method = null, $endpoints = null, $body = null)
    {
        $consumer = MauticConsumer::whereNotNull('id')->orderBy('created_at', 'desc')->first();

        $expirationStatus = $this->factory->checkExpirationTime($consumer->expires);

        if ($expirationStatus) {
            $consumer = $this->factory->refreshToken($consumer->refresh_token);
        }

        return $this->factory->callMautic($method, $endpoints, $body, $consumer->access_token);
    }
}
