<?php namespace Xinix\Blog\Models;

use Str;
use Model;
use Url;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;

class Game extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\NestedTree;

    public $table = 'xinix_blog_games';
    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'slug' => 'required|between:3,64|unique',
    ];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'name',
        ['slug', 'index' => true]
    ];

    protected $guarded = [];

    public $belongsToMany = [
        'posts' => ['Xinix\Blog\Models\Post',
            'table' => 'xinix_blog_posts_games',
            'order' => 'published_at desc',
            'scope' => 'isPublished'
        ],
        'posts_count' => ['Xinix\Blog\Models\Post',
            'table' => 'xinix_blog_posts_games',
            'scope' => 'isPublished',
            'count' => true
        ]
    ];

    public function beforeValidate()
    {
        // Generate a URL slug for this model
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function afterDelete()
    {
        $this->posts()->detach();
    }

    public function getPostCountAttribute()
    {
        return optional($this->posts_count->first())->count ?? 0;
    }

    /**
     * Count posts in this and nested categories
     * @return int
     */
    public function getNestedPostCount()
    {
        return $this->post_count + $this->children->sum(function ($category) {
            return $category->getNestedPostCount();
        });
    }

    /**
     * Sets the "url" attribute with a URL to this object
     *
     * @param string $pageName
     * @param Cms\Classes\Controller $controller
     *
     * @return string
     */
    public function setUrl($pageName, $controller)
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug
        ];

        return $this->url = $controller->pageUrl($pageName, $params, false);
    }

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     * Returns a menu item type information. The type information is returned as array
     * with the following elements:
     * - references - a list of the item type reference options. The options are returned in the
     *   ["key"] => "title" format for options that don't have sub-options, and in the format
     *   ["key"] => ["title"=>"Option title", "items"=>[...]] for options that have sub-options. Optional,
     *   required only if the menu item type requires references.
     * - nesting - Boolean value indicating whether the item type supports nested items. Optional,
     *   false if omitted.
     * - dynamicItems - Boolean value indicating whether the item type could generate new menu items.
     *   Optional, false if omitted.
     * - cmsPages - a list of CMS pages (objects of the Cms\Classes\Page class), if the item type requires a CMS page reference to
     *   resolve the item URL.
     * @param string $type Specifies the menu item type
     * @return array Returns an array
     */
    public static function getMenuTypeInfo($type)
    {
        $result = [];

        if ($type == 'all-blog-games') {
            $result = [
                'dynamicItems' => true
            ];
        }

        if ($result) {
            $theme = Theme::getActiveTheme();

            $pages = CmsPage::listInTheme($theme, true);
            $cmsPages = [];
            foreach ($pages as $page) {
                if (!$page->hasComponent('blogPosts')) {
                    continue;
                }

                /*
                 * Component must use a game filter with a routing parameter
                 * eg: gameFilter = "{{ :somevalue }}"
                 */
                $properties = $page->getComponentProperties('blogPosts');
                if (!isset($properties['gameFilter']) || !preg_match('/{{\s*:/', $properties['gameFilter'])) {
                    continue;
                }

                $cmsPages[] = $page;
            }

            $result['cmsPages'] = $cmsPages;
        }

        return $result;
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     * Returns information about a menu item. The result is an array
     * with the following keys:
     * - url - the menu item URL. Not required for menu item types that return all available records.
     *   The URL should be returned relative to the website root and include the subdirectory, if any.
     *   Use the Url::to() helper to generate the URLs.
     * - isActive - determines whether the menu item is active. Not required for menu item types that
     *   return all available records.
     * - items - an array of arrays with the same keys (url, isActive, items) + the title key.
     *   The items array should be added only if the $item's $nesting property value is TRUE.
     * @param \Winter\Pages\Classes\MenuItem $item Specifies the menu item.
     * @param \Cms\Classes\Theme $theme Specifies the current theme.
     * @param string $url Specifies the current page URL, normalized, in lower case
     * The URL is specified relative to the website root, it includes the subdirectory name, if any.
     * @return mixed Returns an array. Returns null if the item cannot be resolved.
     */
    public static function resolveMenuItem($item, $url, $theme)
    {
        $result = null;

        if ($item->type == 'all-blog-games') {
            $result = [
                'items' => []
            ];

            $games = self::orderBy('name')->get();
            foreach ($games as $game) {
                $gameItem = [
                    'title' => $game->name,
                    'url'   => self::getGamePageUrl($item->cmsPage, $game, $theme),
                    'mtime' => $game->updated_at
                ];

                $gameItem['isActive'] = $gameItem['url'] == $url;

                $result['items'][] = $gameItem;
            }
        }

        return $result;
    }

    /**
     * Returns URL of a category page.
     *
     * @param $pageCode
     * @param $category
     * @param $theme
     */
    protected static function getGamePageUrl($pageCode, $game, $theme)
    {
        $page = CmsPage::loadCached($theme, $pageCode);
        if (!$page) {
            return;
        }

        $properties = $page->getComponentProperties('blogPosts');
        if (!isset($properties['gameFilter'])) {
            return;
        }

        /*
         * Extract the routing parameter name from the category filter
         * eg: {{ :someRouteParam }}
         */
        if (!preg_match('/^\{\{([^\}]+)\}\}$/', $properties['gameFilter'], $matches)) {
            return;
        }

        $paramName = substr(trim($matches[1]), 1);
        $url = CmsPage::url($page->getBaseFileName(), [$paramName => $game->slug]);

        return $url;
    }
}
