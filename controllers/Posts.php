<?php namespace Xinix\Blog\Controllers;

use BackendMenu;
use Flash;
use Lang;
use Backend\Classes\Controller;
use Xinix\Blog\Models\Post;

class Posts extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\ImportExportController::class
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $importExportConfig = 'config_import_export.yaml';

    public $requiredPermissions = ['xinix.blog.access_other_posts', 'xinix.blog.access_posts'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xinix.Blog', 'blog', 'posts');
    }

    public function index()
    {
        $this->vars['postsTotal'] = Post::count();
        $this->vars['postsPublished'] = Post::isPublished()->count();
        $this->vars['postsDrafts'] = $this->vars['postsTotal'] - $this->vars['postsPublished'];

        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        BackendMenu::setContextSideMenu('new_post');

        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/xinix/blog/assets/css/xinix.blog-preview.css');
        $this->addJs('/plugins/xinix/blog/assets/js/post-form.js');

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/xinix/blog/assets/css/xinix.blog-preview.css');
        $this->addJs('/plugins/xinix/blog/assets/js/post-form.js');

        return $this->asExtension('FormController')->update($recordId);
    }

    public function export()
    {
        $this->addCss('/plugins/xinix/blog/assets/css/xinix.blog-export.css');

        return $this->asExtension('ImportExportController')->export();
    }

    public function listExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['xinix.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['xinix.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendModel($model)
    {
        if ($model->exists && !empty($model->slug) && $model->preview_page) {
            $model->setUrl($model->preview_page, (new \Cms\Classes\Controller()));
        }
    }

    public function formExtendFieldsBefore($widget)
    {
        if (!$model = $widget->model) {
            return;
        }

        // @TODO: This shouldn't engage when the translate plugin is present but disabled
        // Fix can be more restrictive checks here or finishing changes to the class loader so that
        // disabled plugins cannot even have their classes loaded.
        if ($model instanceof Post && $model->isClassExtendedWith('Winter.Translate.Behaviors.TranslatableModel')) {
            $widget->secondaryTabs['fields']['content']['type'] = 'Xinix\Blog\FormWidgets\MLBlogMarkdown';
        }
    }

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $postId) {
                if ((!$post = Post::find($postId)) || !$post->canEdit($this->user)) {
                    continue;
                }

                $post->delete();
            }

            Flash::success(Lang::get('xinix.blog::lang.post.delete_success'));
        }

        return $this->listRefresh();
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->published) {
            return 'safe disabled';
        }
    }

    public function formBeforeCreate($model)
    {
        $model->user_id = $this->user->id;
    }

    public function onRefreshPreview()
    {
        $data = post('Post');

        $previewHtml = Post::formatHtml($data['content'], true);

        return [
            'preview' => $previewHtml
        ];
    }
}
