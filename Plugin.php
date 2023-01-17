<?php namespace Xinix\Blog;

use Backend;
use Backend\Models\UserRole;
use Event;
use System\Classes\PluginBase;
use Xinix\Blog\Classes\TagProcessor;
use Xinix\Blog\Models\Category;
use Xinix\Blog\Models\Post;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'xinix.blog::lang.plugin.name',
            'description' => 'xinix.blog::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-pencil',
            'homepage'    => 'https://github.com/wintercms/wn-blog-plugin',
            'replaces'    => ['RainLab.Blog' => '<= 1.5.0'],
        ];
    }

    public function registerComponents()
    {
        return [
            'Xinix\Blog\Components\Post'       => 'blogPost',
            'Xinix\Blog\Components\Posts'      => 'blogPosts',
            'Xinix\Blog\Components\Categories' => 'blogCategories',
            'Xinix\Blog\Components\Games'      => 'blogGames',
            'Xinix\Blog\Components\RssFeed'    => 'blogRssFeed'
        ];
    }

    public function registerPermissions()
    {
        return [
            'xinix.blog.manage_settings' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.manage_settings',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_posts' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_posts',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_categories' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_categories',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_games' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_games',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_other_posts' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_other_posts',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_import_export' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_import_export',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'xinix.blog.access_publish' => [
                'tab'   => 'xinix.blog::lang.blog.tab',
                'label' => 'xinix.blog::lang.blog.access_publish',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ]
        ];
    }

    public function registerNavigation()
    {
        return [
            'blog' => [
                'label'       => 'xinix.blog::lang.blog.menu_label',
                'url'         => Backend::url('xinix/blog/posts'),
                'icon'        => 'icon-pencil',
                'iconSvg'     => 'plugins/xinix/blog/assets/images/blog-icon.svg',
                'permissions' => ['xinix.blog.*'],
                'order'       => 300,

                'sideMenu' => [
                    'new_post' => [
                        'label'       => 'xinix.blog::lang.posts.new_post',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('xinix/blog/posts/create'),
                        'permissions' => ['xinix.blog.access_posts']
                    ],
                    'posts' => [
                        'label'       => 'xinix.blog::lang.blog.posts',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('xinix/blog/posts'),
                        'permissions' => ['xinix.blog.access_posts']
                    ],
                    'categories' => [
                        'label'       => 'xinix.blog::lang.blog.categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('xinix/blog/categories'),
                        'permissions' => ['xinix.blog.access_categories']
                    ],
                    'games' => [
                        'label'       => 'xinix.blog::lang.blog.games',
                        'icon'        => 'icon-gamepad',
                        'url'         => Backend::url('xinix/blog/games'),
                        'permissions' => ['xinix.blog.access_games']
                    ]
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'blog' => [
                'label' => 'xinix.blog::lang.blog.menu_label',
                'description' => 'xinix.blog::lang.blog.settings_description',
                'category' => 'xinix.blog::lang.blog.menu_label',
                'icon' => 'icon-pencil',
                'class' => 'Xinix\Blog\Models\Settings',
                'order' => 500,
                'keywords' => 'blog post category',
                'permissions' => ['xinix.blog.manage_settings']
            ]
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {
        /*
         * Register the image tag processing callback
         */
        TagProcessor::instance()->registerCallback(function($input, $preview) {
            if (!$preview) {
                return $input;
            }

            return preg_replace('|\<img src="image" alt="([0-9]+)"([^>]*)\/>|m',
                '<span class="image-placeholder" data-index="$1">
                    <span class="upload-dropzone">
                        <span class="label">Click or drop an image...</span>
                        <span class="indicator"></span>
                    </span>
                </span>',
            $input);
        });
    }

    public function boot()
    {
        /*
         * Register menu items for the Winter.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'blog-category'       => 'xinix.blog::lang.menuitem.blog_category',
                'all-blog-categories' => 'xinix.blog::lang.menuitem.all_blog_categories',
                'blog-post'           => 'xinix.blog::lang.menuitem.blog_post',
                'all-blog-posts'      => 'xinix.blog::lang.menuitem.all_blog_posts',
                'category-blog-posts' => 'xinix.blog::lang.menuitem.category_blog_posts',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::getMenuTypeInfo($type);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::resolveMenuItem($item, $url, $theme);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
