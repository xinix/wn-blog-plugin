<?php namespace Xinix\Blog\Components;

use Lang;
use Response;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Xinix\Blog\Models\Post as BlogPost;
use Xinix\Blog\Models\Category as BlogCategory;

class RssFeed extends ComponentBase
{
    /**
     * A collection of posts to display
     * @var Collection
     */
    public $posts;

    /**
     * If the post list should be filtered by a category, the model to use.
     * @var Model
     */
    public $category;

    /**
     * Reference to the page name for the main blog page.
     * @var string
     */
    public $blogPage;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $postPage;

    public function componentDetails()
    {
        return [
            'name'        => 'xinix.blog::lang.settings.rssfeed_title',
            'description' => 'xinix.blog::lang.settings.rssfeed_description'
        ];
    }

    public function defineProperties()
    {
        return [
            'categoryFilter' => [
                'title'       => 'xinix.blog::lang.settings.posts_filter',
                'description' => 'xinix.blog::lang.settings.posts_filter_description',
                'type'        => 'string',
                'default'     => '',
            ],
            'sortOrder' => [
                'title'       => 'xinix.blog::lang.settings.posts_order',
                'description' => 'xinix.blog::lang.settings.posts_order_description',
                'type'        => 'dropdown',
                'default'     => 'created_at desc',
            ],
            'postsPerPage' => [
                'title'             => 'xinix.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'xinix.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
            ],
            'blogPage' => [
                'title'       => 'xinix.blog::lang.settings.rssfeed_blog',
                'description' => 'xinix.blog::lang.settings.rssfeed_blog_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'xinix.blog::lang.settings.group_links',
            ],
            'postPage' => [
                'title'       => 'xinix.blog::lang.settings.posts_post',
                'description' => 'xinix.blog::lang.settings.posts_post_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'xinix.blog::lang.settings.group_links',
            ],
        ];
    }

    public function getBlogPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getSortOrderOptions()
    {
        $options = BlogPost::$allowedSortingOptions;

        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return $options;
    }

    public function onRun()
    {
        $this->prepareVars();

        $xmlFeed = $this->renderPartial('@default');

        return Response::make($xmlFeed, '200')->header('Content-Type', 'text/xml');
    }

    protected function prepareVars()
    {
        $this->blogPage = $this->page['blogPage'] = $this->property('blogPage');
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->category = $this->page['category'] = $this->loadCategory();
        $this->posts = $this->page['posts'] = $this->listPosts();

        $this->page['link'] = $this->pageUrl($this->blogPage);
        $this->page['rssLink'] = $this->currentPageUrl();
    }

    protected function listPosts()
    {
        $category = $this->category ? $this->category->id : null;

        /*
         * List all the posts, eager load their categories
         */
        $posts = BlogPost::with('categories')->listFrontEnd([
            'sort'     => $this->property('sortOrder'),
            'perPage'  => $this->property('postsPerPage'),
            'category' => $category
        ]);

        /*
         * Add a "url" helper attribute for linking to each post and category
         */
        $posts->each(function($post) {
            $post->setUrl($this->postPage, $this->controller);
        });

        return $posts;
    }

    protected function loadCategory()
    {
        if (!$categoryId = $this->property('categoryFilter')) {
            return null;
        }

        if (!$category = BlogCategory::whereSlug($categoryId)->first()) {
            return null;
        }

        return $category;
    }
}
