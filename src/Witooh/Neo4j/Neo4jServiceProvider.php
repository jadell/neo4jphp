<?php
namespace Witooh\Neo4j;

use Illuminate\Support\ServiceProvider;
use Witooh\Neo4j\Cypher\Mapper;
use Witooh\Neo4j\Cypher\Query;
use Witooh\Neo4j\Index\Index;

class Neo4jServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('neo4j.cypher.query', function($app){
            $app['curl']->setBaseUrl($app['config']->get('neo4j.base_url'));
            return new Query($app['curl']);
        });

        $this->app->singleton('neo4j.cypher.mapper', function(){
            return new Mapper();
        });

        $this->app->singleton('neo4j.index', function($app){
            return new Index($app['neo4j.cypher.query']);
        });

        $this->app->singleton('neo4j', function(){
            return new Neo4jClient();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'neo4j',
            'neo4j.cypher.query',
            'neo4j.cypher.mapper',
            'neo4j.index',
        ];
    }

}