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
                return $item['username'];
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
        return '<strong><a href="'.get_edit_user_link($item['user_id']).'">'.$item['nickname'].'</a>';
    }

    /**
     * Render product column
     *
     * @param $item
     * @return string
     */
    public function column_product($item)
    {
        return '<a href="'.get_edit_post_link($item['product_id']).'">'.$item['title'].'</a>';
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
            'username' => __('Username', 'skyroom'),
            'product' => __('Product', 'skyroom'),
        );
    }
}
