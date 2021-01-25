<?php

namespace Skyroom\Menu;

use Skyroom\Tables\UsersTable;
use Skyroom\Util\Viewer;

/**
 * User submenu
 *
 * @package Skyroom\Menu
 */
class UserSubmenu extends AbstractSubmenu
{
    /**
     * @var UsersTable $usersTable
     */
    private $usersTable;


    /**
     * @var Viewer $viewer
     */
    private $viewer;

    /**
     * Room submenu constructor
     *
     * @param UsersTable $usersTable
     * @param Viewer $viewer
     */
    public function __construct(UsersTable $usersTable, Viewer $viewer)
    {
        $this->viewer = $viewer;
        $this->usersTable = $usersTable;

        // Set user menu attributes
        parent::__construct(
            'skyroom-users',
            __('Users Registered', 'skyroom'),
            __('Registered', 'skyroom'),
            'manage_options'
        );
    }

    /**
     * Display users page
     */
    function display()
    {

//        $data['access_level'] = 2;
//        $data['access'] = 2;
//        $data['product_id'] = 13;
//
//
//        $user_data = get_user_meta(1, '_skyroom_access', true);
//
//        if ($user_data){
//            $accesses = unserialize($user_data);
//            var_dump($accesses);
//            $flag = 0;
//            foreach ($accesses as &$access){
//                if($access['product_id'] == 13){
//                    $access['access_level'] = 2;
//                    $access['access'] = 5;
//                    $flag = 1;
//                }
//            }
//            if($flag == 0){
//                $accesses[] = $data;
//            }
//            var_dump($accesses);
////            $serialize = serialize($accesses);
////            update_user_meta($_POST['user_id'], '_skyroom_access', $serialize, true);
//        }else{
//            $accesses[] = $data;
////            $serialize = serialize($accesses);
////            add_user_meta($_POST['user_id'], '_skyroom_access', $serialize, true);
//            var_dump($accesses);die;
//        }die;




        $this->usersTable->prepare_items();
        $context = [
            'table' => $this->usersTable,
        ];
        $this->viewer->view('users.php', $context);
    }
}
