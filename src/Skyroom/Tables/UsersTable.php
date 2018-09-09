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
     * @param   array $users Table items
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
        $usersCount = count($this->users);
        $this->set_pagination_args(array(
            'total_items' => $usersCount,
            'per_page' => $usersCount,
        ));
        $this->items = $this->users;
    }

    /**
     * General method for rendering columns
     *
     * @param   User   $item
     * @param   string $column_name
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

            default:
                // What?
                break;
        }
    }

    /**
     * Render nickname column
     *
     * @param   User $item Row data
     *
     * @return  string Rendered item
     */
    public function column_nickname($item)
    {
        $title = '<strong><a href="#">'.$item->getNickname().'</a></strong>';
        if (empty($item->getWpUser())) {
            $url = menu_page_url('skyroom-rooms', false).'&action=remove&user='.$item->getId();
            $actions = array(
                'sync' => sprintf('<a href="'.$url.'">%s</a>', __('Remove from skyroom', 'skyroom')),
            );
        } else {
            $url = get_edit_user_link($item->getWpUser()->ID);
            $actions = array(
                'edit' => sprintf('<a href="'.$url.'">%s</a>', __('Edit user', 'skyroom')),
            );
        }


        return sprintf('%1$s %2$s', $title, $this->row_actions($actions));
    }

    /**
     * Render wp display name column
     *
     * @param   User $item Row data
     *
     * @return  string Rendered item
     */
    function column_wp_display_name($item)
    {
        $wpUser = $item->getWpUser();
        if (empty($wpUser)) {
            return '&mdash;';
        }

        return '<a href="#">'.$wpUser->nickname.'</a>';
    }


    /**
     * Render wp user_login column
     *
     * @param   User $item Row data
     *
     * @return  string Rendered item
     */
    function column_wp_user_login($item)
    {
        $wpUser = $item->getWpUser();
        if (empty($wpUser)) {
            return '&mdash;';
        }

        return '<a href="#">'.$wpUser->user_login.'</a>';
    }

    /**
     * Render checkbox column
     *
     * @param   User $item Row data
     *
     * @return  string Rendered item
     */
    public function column_cb($item)
    {
        return '<input type="checkbox" name="users[]" value="'.$item->getId().'">';
    }

    /**
     * Get table columns
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox">',
            'nickname' => __('Nickname', 'skyroom'),
            'username' => __('Username', 'skyroom'),
            'wp_display_name' => __('Wordpress name', 'skyroom'),
            'wp_user_login' => __('Wordpress username', 'skyroom'),
            'status' => __('Status', 'skyroom'),
        );
    }
}