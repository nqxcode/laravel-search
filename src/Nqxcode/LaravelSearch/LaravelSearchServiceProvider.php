<?php namespace Nqxcode\LaravelSearch;

use Illuminate\Support\ServiceProvider;
use Nqxcode\LaravelSearch\Analyzer\Config as AnalyzerConfig;
use Nqxcode\LaravelSearch\Config as ModelsConfig;

use Config;

use \ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive;

class LaravelSearchServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('nqxcode/laravel-lucene-search');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Nqxcode\LaravelSearch\Search', function ($app) {
            return $app['search'];
        });

        $this->app->bindShared('search', function ($app) {
            return new Search(
                $app['search.connection'],
                $app['search.models.config']
            );
        });

        $this->app['search.analyzer'] = function () {
            return new CaseInsensitive;
        };

        $this->app->bind('Nqxcode\LaravelSearch\Analyzer\Config', function () {
            return new AnalyzerConfig(
                Config::get('laravel-lucene-search::analyzer.filters', []),
                Config::get('laravel-lucene-search::analyzer.stopwords', [])
            );
        });

        $this->app->bindShared('search.index.path', function () {
            return Config::get('laravel-lucene-search::index.path');
        });

        $this->app->bindShared('search.connection', function ($app) {
            return new Connection(
                $app['search.index.path'],
                $app->make('Nqxcode\LaravelSearch\Analyzer\Config')
            );
        });

        $this->app->bindShared('search.index.models', function () {
            return Config::get('laravel-lucene-search::index.models');
        });

        $this->app->bindShared('search.models.config', function ($app) {
            return new ModelsConfig(
                $app['search.index.models'],
                $app->make('Nqxcode\LaravelSearch\ModelFactory')
            );
        });

        $this->app->bindShared('command.search.rebuild', function () {
            return new Console\RebuildCommand;
        });

        $this->app->bindShared('command.search.clear', function () {
            return new Console\ClearCommand;
        });

        $this->commands(array('command.search.rebuild', 'command.search.clear'));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('search', 'command.search.rebuild', 'command.search.clear');
    }
}
