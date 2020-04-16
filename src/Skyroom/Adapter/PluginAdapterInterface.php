<?php

namespace Skyroom\Adapter;

use Skyroom\Entity\Enrollment;
use Skyroom\Entity\ProductWrapperInterface;

/**
 * Plugin adapter interface
 *
 * @package Skyroom\Adapter
 */
interface PluginAdapterInterface
{
    const SKYROOM_ENROLLMENT_SYNCED_META_KEY = '_skyroom_synced_enrollment';

    /**
     * Setup plugin adapter
     *
     * @return mixed
     */
    function setup();

    /**
     * Get plugin specific products that linked to skyroom rooms
     *
     * @param array $roomIds
     *
     * @return ProductWrapperInterface[]
     */
    function getProducts($roomIds);

    /**
     * Get enrollments (purchases) that are not saved on skyroom
     */
    function getUnsyncedEnrolls();

    /**
     * Set synced meta for enrollments
     *
     * @param int[] $itemIds
     */
    function setEnrollmentsSynced($itemIds);

    /**
     * Get product by it's skyroom id
     *
     * @param $skyroomId
     * @return ProductWrapperInterface
     */
    function getProductBySkyroomId($skyroomId);

    /**
     * Check that user bought product
     *
     * @param int $userId
     * @param ProductWrapperInterface $product
     * @return mixed
     */
    function userBoughtProduct($userId, $product);

    /**
     * Get all enrollments of user
     *
     * @param int $userId
     * @return Enrollment[]
     */
    function getUserEnrollments($userId);

    /**
     * Get singular or plural form of specific post type string
     *
     * @param bool $plural Whether to get plural or singular form
     *
     * @return string
     */
    function getPostString($plural = false);
}