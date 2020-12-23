<?php

namespace Skyroom\Tables;

use Skyroom\Entity\Event;
use Skyroom\Repository\EventRepository;

/**
 * Events table.
 *
 * @package Skyroom\Tables
 */
class EventsTable extends WPListTable
{
    /**
     * @var EventRepository
     */
    private $repository;

    /**
     * EventsTable constructor
     *
     * @param EventRepository $repository repository
     */
    public function __construct(EventRepository $repository)
    {
        parent::__construct(array(
            'singular' => __('Event', 'skyroom'),
            'plural' => __('Events', 'skyroom'),
            'ajax' => false,
        ));

        $this->repository = $repository;
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
        $perPage = $this->get_items_per_page('skyroom_events_per_page');

        $data = $this->repository->getAll($perPage, ($pageNum - 1) * $perPage);
        $all = $this->repository->countAll();
        $this->set_pagination_args(array(
            'total_items' => $all,
            'per_page' => $perPage,
        ));
        $this->items = $data;
    }

    /**
     * General method for rendering columns
     *
     * @param Event  $event
     * @param string $column_name
     *
     * @return string Rendered item
     */
    public function column_default($event, $column_name)
    {
        switch ($column_name) {
            case 'title':
                return '<strong>'.$event->getTitle().'</strong>';

            case 'details':
                return '<a href="#" class="show-details" data-details="'
                    .esc_attr(json_encode($event->getErrorInfo())).'">'.__('Show details', 'skyroom').'</a>';

            case 'type':
                return $event->getType() === Event::SUCCESSFUL
                    ? '<span class="skyroom-event-type skyroom-event-type-successful">'.__('Successful', 'skyroom').'</span>'
                    : '<span class="skyroom-event-type skyroom-event-type-failed">'.__('Failed', 'skyroom').'</span>';

            case 'created_at':
                return date_i18n(__('j F Y, H:i:s', 'skyroom'), $event->getCreatedAt());

            default:
                // What?
                break;
        }
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
            'details' => __('Details', 'skyroom'),
            'type' => __('Type', 'skyroom'),
            'created_at' => __('Created at', 'skyroom'),
        );
    }

    /**
     * @param Event $event
     */
    public function single_row($event)
    {
        echo '<tr class="'.($event->getType() === Event::SUCCESSFUL ? 'successful' : 'failed').'">';
        $this->single_row_columns($event);
        echo '</tr>';
    }


}
