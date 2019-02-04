<?php

do_action('skyroom_before_login_form');

wp_login_form();

do_action('skyroom_after_login_form');
