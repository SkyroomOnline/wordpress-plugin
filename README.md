<p align="center"><img src="https://user-images.githubusercontent.com/1191702/47265312-ead97100-d532-11e8-901b-632a204a2d91.png"></p>

# Skyroom Wordpress Plugin
A plugin to integrate skyroom service with your wordpress site.

You can manage your sells, customers and other with wordpress and let this plugin manage skyroom related stuff for you. 

**(For now, only WooCommerce is supported. But it's planned to support all major wordpress commerce plugins.)**

## Installation
Download latest build from [releases](https://github.com/SkyroomOnline/wordpress-plugin/releases) (skyroom.zip) and upload it to your wordpress plugins directory and activate the plugin.
Then set up plugin with your web service info and after successful set up, from Skyroom/Synchronization menu run synchronization to sync your wordpress users with your skyroom service.

## Working with plugin
This plugin sits between your wordpress site and your skyroom service and does things automaticlly for you.

Just create the products in following way. Other things such as:
* Creating a new room associated with your product.
* Registering users on your skyroom service whenever a user registers in wordpress.
* Assigning user to room whenever user purchases associated product.
* Showing "Go to room" button for users that purchased product.

Will be done automatically for you.

### Create woocommerce product
Select the 'skyroom' as your Product Type and fill room details on skyroom tab.

![skyroom-product](https://user-images.githubusercontent.com/1191702/47265648-2296e780-d538-11e8-9de0-ebc71ffd6257.png)

### Dedicated user enrollments page
Normally users can enter to enrolled classes by visiting class product page. In product page, "Add to card" button turns to "Enter class" for users that enrolled to class.

To show all enrolled classes to user in one place, you can use `[SkyroomEnrollments]` shortcode in a custom page. This shortcode displays enrollments in a table.

You can add markup before and after table by `skyroom_before_enrollments_table` and `skyroom_after_enrollments_table` hooks and style table as you want.

## Support
If you found any issues or you have any idea to make the plugin better, feel free to open an issue. We greatly appreciate your feedback.

Alternatively you can contact us directly from [contact form](https://www.skyroom.online/contact).
