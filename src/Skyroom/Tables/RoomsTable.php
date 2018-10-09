<?php

namespace Skyroom\Tables;

use Skyroom\Entity\Room;

/**
 * Rooms table.
 *
 * @package Skyroom\Tables
 */
class RoomsTable extends WPListTable
{
    /**
     * @var array
     */
    private $rooms;

    /**
     * @var string
     */
    private $postTypeString;

    /**
     * RoomsTable constructor
     *
     * @param array  $rooms Table items
     * @param string $postTypeString
     */
    public function __construct($rooms, $postTypeString)
    {
        parent::__construct(array(
            'singular' => __('Room', 'skyroom'),
            'plural' => __('Rooms', 'skyroom'),
            'ajax' => false,
        ));

        $this->rooms = $rooms;
        $this->postTypeString = $postTypeString;
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
        $roomsCount = count($this->rooms);
        $this->set_pagination_args(array(
            'total_items' => $roomsCount,
            'per_page' => $roomsCount,
        ));
        $this->items = $this->rooms;
    }

    /**
     * General method for rendering columns
     *
     * @param Room   $item
     * @param string $column_name
     *
     * @return string Rendered item
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                return $item->getName();
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
     * Render title column
     *
     * @param Room $item Row data
     *
     * @return string Rendered item
     */
    public function column_title($item)
    {
        return '<strong>'.$item->getTitle().'</strong>';
    }

    /**
     * Render post column
     *
     * @param Room $item
     *
     * @return string Rendered item
     */
    public function column_post($item)
    {
        $product = $item->getProduct();
        if (empty($product)) {
            return '&mdash;';
        }

        return '<a href="'.get_edit_post_link($product->getId()).'">'.$product->getTitle().'</a>';
    }

    /**
     * Get table columns
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'title' => __('Title', 'skyroom'),
            'post' => $this->postTypeString,
            'name' => __('Name', 'skyroom'),
            'status' => __('Status', 'skyroom'),
        );
    }
}