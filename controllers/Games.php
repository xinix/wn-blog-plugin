<?php namespace Xinix\Blog\Controllers;

use BackendMenu;
use Flash;
use Lang;
use Backend\Classes\Controller;
use Xinix\Blog\Models\Game;

class Games extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['xinix.blog.access_categories'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xinix.Blog', 'blog', 'games');
    }

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $gameId) {
                if ((!$game = Game::find($gameId))) {
                    continue;
                }

                $game->delete();
            }

            Flash::success(Lang::get('xinix.blog::lang.game.delete_success'));
        }

        return $this->listRefresh();
    }
}
