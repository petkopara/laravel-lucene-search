<?php namespace Nqxcode\LuceneSearch;

use Config;
use Nqxcode\LuceneSearch\Analyzer\Config as AnalyzerConfig;
use Nqxcode\LuceneSearch\Analyzer\Stopwords\FilterFactory;
use Nqxcode\LuceneSearch\Index\Connection;
use Nqxcode\LuceneSearch\Model\Config as ModelsConfig;
use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
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
        $this->app->bind('Nqxcode\LuceneSearch\Search', function ($app) {
            return $app['search'];
        });

        $this->app->bindShared('search', function ($app) {
            return new Search(
                $app['search.connection'],
                $app['search.models.config']
            );
        });

        $this->app->bind('ZendSearch\Lucene\Analysis\Analyzer\Common\AbstractCommon', function () {
            return new CaseInsensitive;
        });

        $this->app->bind('Nqxcode\LuceneSearch\Analyzer\Config', function () {
            return new AnalyzerConfig(
                Config::get('laravel-lucene-search::analyzer.filters', []),
                Config::get('laravel-lucene-search::analyzer.stopwords', []),
                new FilterFactory
            );
        });

        $this->app->bindShared('search.index.path', function () {
            return Config::get('laravel-lucene-search::index.path');
        });

        $this->app->bindShared('search.connection', function ($app) {
            return new Connection(
                $app['search.index.path'],
                $app->make('Nqxcode\LuceneSearch\Analyzer\Config')
            );
        });

        $this->app->bindShared('search.models.config', function ($app) {
            return new ModelsConfig(
                Config::get('laravel-lucene-search::index.models'),
                $app->make('Nqxcode\LuceneSearch\Model\Factory')
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
