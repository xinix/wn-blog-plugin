<?php namespace Xinix\Blog\Components;

use Db;
use Carbon\Carbon;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Xinix\Blog\Models\Game as BlogGame;

class Games extends ComponentBase
{
    /**
     * @var Collection A collection of games to display
     */
    public $games;

    /**
     * @var string Reference to the page name for linking to games.
     */
    public $gamePage;

    /**
     * @var string Reference to the current game slug.
     */
    public $currentGameSlug;

    public function componentDetails()
    {
        return [
            'name'        => 'xinix.blog::lang.settings.game_title',
            'description' => 'xinix.blog::lang.settings.game_description'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'xinix.blog::lang.settings.game_slug',
                'description' => 'xinix.blog::lang.settings.game_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
            'displayEmpty' => [
                'title'       => 'xinix.blog::lang.settings.game_display_empty',
                'description' => 'xinix.blog::lang.settings.game_display_empty_description',
                'type'        => 'checkbox',
                'default'     => 0,
            ],
            'gamePage' => [
                'title'       => 'xinix.blog::lang.settings.game_page',
                'description' => 'xinix.blog::lang.settings.game_page_description',
                'type'        => 'dropdown',
                'default'     => 'blog/game',
                'group'       => 'xinix.blog::lang.settings.group_links',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->currentGameSlug = $this->page['currentGameSlug'] = $this->property('slug');
        $this->gamePage = $this->page['gamePage'] = $this->property('gamePage');
        $this->games = $this->page['games'] = $this->loadGames();
    }

    /**
     * Load all games or, depending on the <displayEmpty> option, only those that have blog posts
     * @return mixed
     */
    protected function loadGames()
    {
        $games = BlogGame::with('posts_count')->getNested();
        /*
         * Add a "url" helper attribute for linking to each game
         */
        return $this->linkGames($games);
    }

    /**
     * Sets the URL on each game according to the defined game page
     * @return void
     */
    protected function linkGames($games)
    {
        return $games->each(function ($game) {
            $game->setUrl($this->gamePage, $this->controller);
        });
    }
}
