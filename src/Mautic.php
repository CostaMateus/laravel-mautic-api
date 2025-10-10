<?php

namespace Triibo\Mautic;

use Mautic\{Factory, MauticConsumer};
use Triibo\Mautic\Factories\MauticFactory;
use GrahamCampbell\Manager\AbstractManager;
use Illuminate\Contracts\Config\Repository;

class Mautic extends AbstractManager
{
    /**
     * The factory instance.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * Create a new Mautic manager instance.
     *
     * @param Repository    $config
     * @param MauticFactory $factory
     *
     * @return void
     */
    public function __construct( Repository $config, MauticFactory $factory )
    {
        parent::__construct( $config );

        $this->factory = $factory;
    }

    /**
     * Get the factory instance.
     *
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Makes a request to Mautic.
     *
     * @param null|string $method
     * @param null|string $endpoints
     * @param null|array  $body
     *
     * @return mixed
     */
    public function request( ?string $method = null, ?string $endpoints = null, ?array $body = null )
    {
        $consumer = MauticConsumer::whereNotNull( "id" )->orderBy( "created_at", "desc" )->first();

        if ( empty( $consumer ) ) {
            // No consumer exists, create new one via OAuth flow
            $consumer = $this->factory->make( config( "mautic.connections.main" ) );
        } elseif ( $this->factory->checkExpirationTime( $consumer->expires ) ) {
            // Token expired, try to refresh first
            if ( $consumer->refresh_token ) {
                $refreshedConsumer = $this->factory->refreshToken( $consumer->refresh_token );
                
                if ( $refreshedConsumer instanceof MauticConsumer ) {
                    $consumer = $refreshedConsumer;
                } else {
                    // Refresh failed, fall back to new OAuth flow
                    $consumer = $this->factory->make( config( "mautic.connections.main" ) );
                }
            } else {
                // No refresh token available, create new one via OAuth flow
                $consumer = $this->factory->make( config( "mautic.connections.main" ) );
            }
        }

        return $this->factory->callMautic( $method, $endpoints, $body, $consumer->access_token );
    }

    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return object
     */
    protected function createConnection( array $config ) : object
    {
        return $this->factory->make( $config );
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName() : string
    {
        return "mautic";
    }
}
