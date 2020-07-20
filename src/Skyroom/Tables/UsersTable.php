<?php

namespace Skyroom\Tables;

use Skyroom\Entity\User;

/**
 * Rooms table.
 *
 * @package Skyroom\Tables
 */
class UsersTable extends WPListTable
{
    /**
     * @var     array $users
     */
    private $users;

    /**
     * RoomsTable constructor.
     *
     * @param array $users Table items
     */
    public function __construct($users)
    {
        parent::__construct(array(
            'singular' => __('User', 'skyroom'),
            'plural' => __('Users', 'skyroom'),
            'ajax' => false,
        ));

        $this->users = $users;
    }

    /**
     * Prepare table items
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
//        if ($this->users) {
            $usersCount = count($this->users);
//        } else {
//            $usersCount = 0;
//        }
        $this->set_pagination_args(array(
            'total_items' => $usersCount,
            'per_page' => $usersCount,
        ));
        $this->items = $this->users;
    }

    /**
     * General method for rendering columns
     *
     * @param User $item
     * @param string $column_name
     *
     * @return  string Rendered item
     */
    public function column_default($item, $column_name)
    {

        switch ($column_name) {
            case 'username':
                return $item->getUsername();
                break;

            case 'status':
                return $item->getStatusAsString();
                break;

            case 'product':

                return $item->getProductName();
                break;

            default:
                // What?
                break;
        }
    }

    /**
     * Render nickname column
     *
     * @param User $item Row data
     *
     * @return  string Rendered item
     */
    public function column_nickname($item)
    {
        if (empty($wpUser = $item->getWpUser())) {
            return '<strong>' . $item->getNickname() . '</strong>';
        } else {
            return '<strong><a href="' . get_edit_user_link($item->getWpUser()->ID) . '">' . $item->getNickname() . '</a>';
        }
    }

    /**
     * Render wp user_login column
     *
     * @param User $item Row data
     *
     * @return  string Rendered item
     */
    function column_wp_user_login($item)
    {
        $wpUser = $item->getWpUser();
        if (empty($wpUser)) {
            return '&mdash;';
        }

        return $wpUser->user_login;
    }

    /**
     * Get table columns
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'nickname' => __('Nickname', 'skyroom'),
            'wp_user_login' => __('Wordpress username', 'skyroom'),
            'product' => __('Product', 'skyroom'),
//            'status' => __('Status', 'skyroom'),
        );
    }
}