<?php

defined('WPINC') || die;


if (empty($enrollments)) : ?>
    <p class="<?php echo apply_filters('skyroom_no_enrollment_class', 'no-enrollments') ?>">
        <strong>
            <?php echo apply_filters('skyroom_no_enrollment_class', __('You are not attended to any course yet.', 'skyroom')) ?>
        </strong>
    </p>
<?php else: ?>
    <?php do_action('skyroom_before_enrollments_table') ?>

    <h2><?php echo apply_filters('skyroom_enrollments_enrolled_courses', __('Enrolled Courses', 'skyroom')) ?></h2>

    <table class="<?php echo apply_filters('skyroom_enrollments_table_class', 'enrollments') ?>">
        <thead>
            <tr>
                <th><?php echo apply_filters('skyroom_enrollments_course_name_heading', __('Course Title', 'skyroom')) ?></th>
                <th><?php echo apply_filters('skyroom_enrollments_enroll_date_heading', __('Enrollment Date', 'skyroom')) ?></th>
                <th><?php echo apply_filters('skyroom_enrollments_enter_class_text', __('Enter class', 'skyroom')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($enrollments as $enrollment) : ?>
            <tr>
                <td><?php echo $enrollment->getProduct()->getTitle() ?></td>
                <td><?php echo date_i18n('j F Y', $enrollment->getEnrollTime()) ?></td>
                <td>
                    <a href="<?php echo home_url('redirect-to-room/'.$enrollment->getProduct()->getId()) ?>" class="button alt">
                        <?php echo apply_filters('skyroom_enrollments_enter_class_text', __('Enter class', 'skyroom')) ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>
