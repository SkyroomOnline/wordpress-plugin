<?php

namespace Skyroom\Controller;

/**
 * Class Skyroom
 *
 * @package Skyroom\Controller
 */
class UserController
{
    const setUserData = 'skyroom_set_user_data';
    const getUserData = 'skyroom_get_user_data';

    public function __construct()
    {
    }

    public function getData()
    {
        check_ajax_referer(UserController::getUserData, 'nonce');
        if (isset($_POST['user_id'])) {
            $data = [];
            $str = '';
            $product_id = $_POST['product_id'];
            $user_data = get_user_meta($_POST['user_id'], '_skyroom_access', true);
            if ($user_data) {
                $accesses = unserialize($user_data);
                $flag = 0;
                foreach ($accesses as $access) {
                    if ($access['product_id'] == $product_id) {
                        $data['access_level'] = $access['access_level'];
                        $data['access'] = $access['access'];
                        $flag = 1;
                    }
                }
                if ($flag == 0) {
                    $str = $str . '<option value="1" selected>' . __('Normal role', 'skyroom') . '</option>';
                    $str = $str . '<option value="2">' . __('Presenter role', 'skyroom') . '</option>';
                    $str = $str . '<option value="3">' . __('Operator role', 'skyroom') . '</option>';
                } else {
                    if ($data['access_level'] == '1') {
                        $str = $str . '<option value="1" selected>' . __('Normal role', 'skyroom') . '</option>';
                        $str = $str . '<option value="2">' . __('Presenter role', 'skyroom') . '</option>';
                        $str = $str . '<option value="3">' . __('Operator role', 'skyroom') . '</option>';
                    } elseif ($data['access_level'] == '2') {
                        $str = $str . '<option value="1" selected>' . __('Normal role', 'skyroom') . '</option>';
                        $str = $str . '<option value="2" selected>' . __('Presenter role', 'skyroom') . '</option>';
                        $str = $str . '<option value="3">' . __('Operator role', 'skyroom') . '</option>';
                    } elseif ($data['access_level'] == '3') {
                        $str = $str . '<option value="1" selected>' . __('Normal role', 'skyroom') . '</option>';
                        $str = $str . '<option value="2">' . __('Presenter role', 'skyroom') . '</option>';
                        $str = $str . '<option value="3" selected>' . __('Operator role', 'skyroom') . '</option>';
                    }
                }
            } else {
                $str = $str . '<option value="1" selected>' . __('Normal role', 'skyroom') . '</option>';
                $str = $str . '<option value="2">' . __('Presenter role', 'skyroom') . '</option>';
                $str = $str . '<option value="3">' . __('Operator role', 'skyroom') . '</option>';
            }
            wp_send_json_success($str);
        }
    }

    public function setData()
    {
        check_ajax_referer(UserController::setUserData, 'nonce');
        if (isset($_POST)) {
            $data['access_level'] = $_POST['access_level'];
            $data['access'] = $_POST['access'];
            $data['product_id'] = $_POST['product_id'];

            $user_data = get_user_meta($_POST['user_id'], '_skyroom_access', true);

            if ($user_data) {
                $accesses = unserialize($user_data);
                $flag = 0;
                foreach ($accesses as &$access) {
                    if ($access['product_id'] == $_POST['product_id']) {
                        $access['access_level'] = $_POST['access_level'];
                        $access['access'] = $_POST['access'];
                        $flag = 1;
                    }
                }
                if ($flag == 0) {
                    $accesses[] = $data;
                }
                $serialize = serialize($accesses);
                update_user_meta($_POST['user_id'], '_skyroom_access', $serialize);
            } else {
                $accesses[] = $data;
                $serialize = serialize($accesses);
                add_user_meta($_POST['user_id'], '_skyroom_access', $serialize, true);
            }

            $json['message'] = __('Your changes saved successfully', 'skyroom');
            wp_send_json_success($json);
        } else {
            $json['message'] = __('Your changes save failed', 'skyroom');
            wp_send_json_error($json);
        }
        wp_die();
    }
}
