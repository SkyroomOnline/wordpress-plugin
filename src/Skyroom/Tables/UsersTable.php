<?php

namespace Skyroom\Tables;

use Skyroom\Adapter\WooCommerceAdapter;

/**
 * Rooms table.
 *
 * @package Skyroom\Tables
 */
class UsersTable extends WPListTable
{
    /**
     * @var WooCommerceAdapter $wooCommerceAdapter
     */
    private $wooCommerceAdapter;

    /**
     * RoomsTable constructor.
     *
     * @param WooCommerceAdapter $wooCommerceAdapter
     */
    public function __construct(WooCommerceAdapter $wooCommerceAdapter)
    {
        parent::__construct(array(
            'singular' => __('User', 'skyroom'),
            'plural' => __('Users', 'skyroom'),
            'ajax' => false,
        ));

        $this->wooCommerceAdapter = $wooCommerceAdapter;
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

        $pageNum = $this->get_pagenum();
//        $perPage = $this->get_items_per_page('skyroom_events_per_page');
        $perPage = 2;

        $data = $this->wooCommerceAdapter->getSkyroomUsers($perPage, ($pageNum - 1) * $perPage);
        $all = count($this->wooCommerceAdapter->getSkyroomUsers());

        $this->set_pagination_args(array(
            'total_items' => $all,
            'per_page'    => $perPage,
        ));
        $this->items = $data;
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
