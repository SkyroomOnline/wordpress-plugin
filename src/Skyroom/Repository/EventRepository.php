<?php

namespace Skyroom\Repository;

use Skyroom\Entity\Event;

/**
 * Event Repository
 *
 * @package Skyroom\Repository
 */
class EventRepository
{
    /**
     * @var \wpdb
     */
    private $db;

    public function __construct(\wpdb $db)
    {
        $this->db = $db;
    }

    /**
     * Save event to database
     *
     * @param Event $event
     *
     * @return false|int
     */
    public function save(Event $event)
    {
        return $this->db->insert(
            $this->db->prefix.'skyroom_events',
            [
                'title' => $event->getTitle(),
                'type' => $event->getType(),
                'info' => serialize($event->getErrorInfo()),
            ]
        );
    }

    /**
     * Get event by id
     *
     * @param $id
     *
     * @return Event
     */
    public function get($id)
    {
        $query = $this->db->prepare("SELECT * FROM {$this->db->prefix}skyroom_events WHERE id=%s", $id);
        $props = $this->db->get_row($query);

        return $this->create($props);
    }

    /**
     * Get bunch of events
     *
     * @param int $limit
     * @param int $offset
     *
     * @return Event[]
     */
    public function getAll($limit = 0, $offset = 0)
    {
        if (!empty($limit) && !empty($offset)) {
            $query = $this->db->prepare("SELECT * FROM {$this->db->prefix}skyroom_events ORDER BY created_at DESC LIMIT %d,%d", $offset,
                $limit);
        } elseif (!empty($limit)) {
            $query = $this->db->prepare("SELECT * FROM {$this->db->prefix}skyroom_events ORDER BY created_at DESC LIMIT %d", $limit);
        } else {
            $query = "SELECT * FROM {$this->db->prefix}skyroom_events ORDER BY created_at DESC";
        }

        $propsArr = $this->db->get_results($query);
        $events = [];
        foreach ($propsArr as $props) {
            $events[] = $this->create($props);
        }

        return $events;
    }

    /**
     * Count all events
     *
     * @return int Number of all events
     */
    public function countAll()
    {
        $query = "SELECT COUNT(*) FROM {$this->db->prefix}skyroom_events";

        return $this->db->get_var($query);
    }

    /**
     * Create Event instance from properties array
     *
     * @param \stdClass $props
     *
     * @return Event
     */
    private function create($props)
    {
        try {
            $event = new Event($props->title, intval($props->type), unserialize($props->info));
            $reflectionEvent = new \ReflectionClass($event);
            $reflectionProperty = $reflectionEvent->getProperty('id');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($event, $props->id);
            $reflectionProperty = $reflectionEvent->getProperty('createdAt');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($event, strtotime($props->created_at.' +0000'));

            return $event;

        } catch (\ReflectionException $exception) {
            var_dump($exception);
        }

        return null;
    }
}