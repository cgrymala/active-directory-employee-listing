# Plugin Name #
**Contributors:** cgrymala

**Donate link:** http://www.umw.edu/gift/

**Tags:** active directory, ldap, employees, users, directory

**Requires at least:** 3.1

**Tested up to:** 3.2

**Stable tag:** 0.2.1a

**License:** GPL2


Retrieve lists of active directory users and display them in WordPress.

## Description ##

This plugin allows you to query an active directory server to retrieve an optionally filtered list of users and display it as a list within a WordPress site. Some of the features of this plugin include:

* Widget - a widget is provided, with a few filtering/formatting options, to allow you to display a user list in any widgetized area
* Shortcode - a shortcode is provided, with all of the plugin's filtering/formatting options, to allow you to display a user list within any page or post. The shortcode is `[ad-employee-list]`.
* Output builder - a full-featured output builder is provided, allowing you to completely customize the format in which each user is displayed within the list. The output builder even allows conditional (if...elseif...else) statements.
* Format options - in addition to the output builder (which is used for each individual user), the following formatting options are also available:
	* before_list - Any HTML code you would like to appear before the list of employees. This code is output before the opening title_wrap tag.
	* after_list - Any HTML code you would like to appear after the list of employees. This code is output after the closing list_wrap tag.
	* after_title - Any HTML code you would like to appear between the closing title_wrap tag and the opening list_wrap tag.
	* title_wrap - The HTML element you would like to use to wrap the list title (if set). Just the element name, please; no opening or closing brackets.
	* title_class - The CSS class you would like applied to the list title (if set). If you would prefer that no CSS class be applied to the title, leave this blank.
	* title_id - If you would like to apply an HTML ID to the list title, you can indicate that here. Remember that IDs should be unique, so, if you plan on using multiple employee lists on a single page, you should leave this blank.
	* title - The title you would like to appear at the top of the list. The title is output prior to the opening of the list itself.
	* list_wrap - The HTML element you would like to use to wrap the entire list. Just the element name, please; no opening or closing brackets.
	* list_class - The CSS class you would like to assign to the opening list_wrap tag, aiding in styling the entire list. If you would prefer that no CSS class be applied to the list, leave this blank.
	* list_id - If you would like to apply an HTML ID to the list itself, you can indicate that here. Remember that IDs should be unique, so, if you plan on using multiple employee lists on a single page, you should leave this blank.
	* item_wrap - The HTML element you would like to use to wrap each individual employee in the list. Just the element name, please; no opening or closing brackets.
	* item_class - The CSS class you would like to assign to each individual employee in the list. If you would prefer that no CSS class be applied to the list, leave this blank.
	* item_id - If you would like to apply an HTML ID to each individual employee in the list, you can indicate that here. You can use placeholder variables for user information (any of the fields that are set to be retrieved, plus the user's username (samaccountname). Simply wrap the placeholder variable with percent symbols (so, to use a placeholder for samaccountname, use %samaccountname%) All disallowed characters (the @ symbol, dots, spaces, etc.) will be replaced with hyphens. Remember that IDs should be unique, so, if you plan on using multiple employee lists that may include the same employee multiple times on a single page, you should leave this blank. Likewise, you should use a placeholder variable that will be unique.
* Field chooser - choose which Active Directory fields to retrieve from the server (note that any fields included in the output builder must be chosen in the field chooser, otherwise they won't be retrieved and, therefore, won't be displayed)
* Single user display - feed a username to the shortcode or widget, and a single user will be retrieved and displayed, rather than a list of users
* Search form - a simple search form (the input keyword is searched against all fields being retrieved) is provided
* Multisite-compatible - Options can be set for the entire network, and can be overridden on each individual site within the network. Some options can even be overridden in the widget or shortcode itself.
* Information cache - Information retrieved by this plugin is cached for 24 hours after it is retrieved (this option is not currently configurable, but will probably be in future versions) in order to avoid hitting the AD server more than necessary.

You can connect to the Active Directory server using SSL and/or TLS, if desired. You can also provide multiple Active Directory server addresses to allow load-balancing (a random server is chosen from the list before connecting and querying).

### Compatibility Note and Credits ###

This plugin currently utilizes version 3.3.2 (with some extended functionality built specifically for this plugin) of the [adLDAP class](http://adldap.sourceforge.net/) from Scott Barnett & Richard Hyland. It has also been tested with version 3.1-Extended and version 3.3.2-Extended as they are included with various versions of the [Active Directory Authentication Integration](http://wordpress.org/extend/plugins/active-directory-authentication-integration/) and [Active Directory Integration](http://wordpress.org/extend/plugins/active-directory-integration/) plugins. This plugin is potentially incompatible (and has not been tested) with Active Directory Integration, though; as it all depends on in which order the adLDAP class is instantiated (if this plugin's copy of adLDAP is included before that plugin's version, that plugin may not work properly).

This plugin was developed by [Curtiss Grymala](http://wordpress.org/support/profile/cgrymala) for the [University of Mary Washington](http://umw.edu/). It is licensed under the GPL2, which basically means you can take it, break it and change it any way you want, as long as the original credit and license information remains somewhere in the package.

### Important Note ###

At this time, this plugin has only been tested on a handful of WordPress installations (all on similar server configurations) with a single Active Directory server, so it is entirely possible that there will be bugs or errors that stop it (or other plugins) from working properly. In order to improve this plugin, please share any feedback you have.

## Installation ##

This plugin can be installed as a normal plugin, a multisite (network-active) plugin or a mu-plugin (must-use).

To install as a normal or multisite plugin manually:

1. Download the ZIP file of the current version
2. Unzip the file on your computer
3. Upload the active-directory-employee-list folder to /wp-content/plugins

To install as a normal or multisite plugin automatically:

1. Visit Plugins -> Add New in your Site Admin (for normal WordPress installations) or Network Admin (for multisite WordPress installations) area
2. Search for Active Directory Employee List
3. Click the "Install" link for this plugin

To activate the plugin on a single site:

1. Go to the Plugins menu within the Site Admin area and click the "Activate" link

To network-activate the plugin on a multisite network:

1. Go to the Plugins menu within the Network Admin area and click the "Activate" link

To install as a mu-plugin:

1. Download the ZIP file of the current version
2. Unzip the file on your computer
3. Upload all of the files inside of the active-directory-employee-list folder into your /wp-content/mu-plugins directory. If you upload the active-directory-employee-list folder itself, you will need to move active-directory-employee-list.php out of that folder so it resides directly in /wp-content/mu-plugins

## Frequently Asked Questions ##

### Why isn't the plugin doing what it should? ###

This is kind of a loaded question. However, the first thing I would suggest is to make sure you're using the latest version of the plugin. If possible, please try the development version of the plugin to make sure that doesn't fix the problem you encountered.

In addition, this plugin relies on a PHP class that's also included in the [Active Directory Authentication Integration plugin](http://wordpress.org/extend/plugins/active-directory-authentication-integration/) (ADAI) and the [Active Directory Integration plugin](http://wordpress.org/extend/plugins/active-directory-integration/). If either of those plugins are installed on your server, especially if they are active, they could be overriding the PHP class definition that this plugin is trying to use. To test things out, please disable/deactivate (and possibly even delete) the ADAI or Active Directory Integration plugin temporarily and see if that fixes the issue. If you are using ADAI, you can update both ADEL and ADAI to the development versions, which should keep the appropriate PHP class in sync.

### How do I use the shortcode? ###

The shortcode for this plugin is `[ad-employee-list]`. The shortcode accepts the following arguments:

* fields - a comma-separated list of the fields you'd like to retrieve
* group - the Active Directory security group or distribution group you'd like to retrieve (if you want to show a list of users)
* username - the samaccountname of the user you would like to display (if you want to show a specific user). If the username is set, the group will be ignored; and only a single employee will appear.
* include_search - whether or not to display the search form for the AD list
* max_results - the maximum number of results that should be retrieved/displayed

### Are there any actions/filters I can use as a developer? ###

Yes, there are a handful of actions and filters built into this plugin. Following is a brief list of them.

**Actions**

* `adel_set_ldap_options` - This is an action available in the plugin. This action is run immediately after opening the LDAP connection. Its main purpose is to allow you to set global LDAP options for this connection. The action sends a single parameter (the LDAP object used for the plugin). To set an LDAP option with this action, it is recommended you use the `set_ldap_option()` method within the LDAP object that's sent. For example, to change the maximum number of results retrieved by this plugin, you would use code similar to the following:
`add_action( 'adel_set_ldap_options', 'my_function_to_set_max_ldap_results' );
function my_function_to_set_max_ldap_results( $ldap ) {
	$ldap->set_ldap_option( LDAP_OPT_SIZELIMIT, 100 );
}`

**Filters**

* `adel-is-ldap-supported` - If you don't have the LDAP extension set up within your PHP configuration, but, for some reason, you still want to be able to test things within this plugin, you can hook into this filter to trick the plugin into thinking you have LDAP set up. Simply hook into this filter and return boolean `true`. This filter basically exists strictly to allow me, as a developer, to test certain aspects of the plugin on my servers that don't have LDAP set up. Obviously, if you don't have LDAP set up within PHP, you won't be able to actually use the plugin, but this filter should at least allow you to configure the settings for the plugin. This filter sends a boolean value to the callback function, and expects a boolean result.
* `adel_search_results` - If you would like to filter the results of a search (to add information above or below the search results, for instance), you can hook into this filter. This filter acts similarly to the native WordPress `the_content` filter, in that it simply sends HTML as the only parameter, and expects HTML as the return value.
* `adel-no-results-text` - If you would like to modify the text that's output when no results are returned by the search, you can hook into this filter. This filter sends the original "no results" text ("No employees could be found matching the criteria specified. Please try a different search. If you searched for a person\'s first name and last name together, please try searching for just the first or last name.") and expects text (or HTML) in return.
* `ad-employee-advanced-search` - If you would like to modify the **advanced** search form in any way, this filter allows you to do that. The filter sends the original HTML for the advanced search form, and expects HTML in return.
* `ad-employee-simple-search` - If you would like to modify the **simple** search form in any way, this filter allows you to do that. The filter sends the original HTML for the advanced search form, and expects HTML in return.
* `ad-employee-custom-search` - There is a hidden shortcode you can use within this plugin. The `ad-employee-custom-search` shortcode allows you to output a completely custom search form in a page or post. By default, the `ad-employee-custom-search` shortcode outputs a blank string. However, that blank string is run through the `ad-employee-custom-search` filter before it is returned, so you can hook into that filter to build any kind of search form you want.
* `adel-simple-presets` - By default, this plugin comes with a handful of presets for the Output format. If you would like to build your own presets, you can hook into this filter to do that. The parameter sent with this filter is an associative array. The keys should be unique for each preset, in order to distinguish which is which. The values for each should be the actual Output Builder template you want to use.
* `adel-need-to-retrieve` - This filter allows you to add to the list of AD parameters that absolutely must be retrieved when building output.

### Where should I seek support if I find a bug or have a question? ###

The best place to seek support is in [the official WordPress support forums](http://wordpress.org/tags/active-directory-employee-list?forum_id=10#postform). If you don't get an answer there, you can try posting a comment on [the official plugin page](http://plugins.ten-321.com/active-directory-employee-list/). Finally, you can [hit me up on Twitter](http://twitter.com/cgrymala) if you want me to take a look at something.

### Will this plugin work in a multisite environment? ###

Yes. It can be activated normally on each individual site or it can be network-activated.

### Will this plugin work in a multi-network environment? ###

Yes. However, at this time, it is not optimized for a multi-network environment (without a set of functions that are currently unavailable to the public, as they are still under development separately from this plugin). Therefore, it will function as any other multisite-compatible plugin would function.

### Can I use this as a mu-plugin? ###

You should be able to, though it will make the plugin potentially even more incompatible with the [Active Directory Integration](http://wordpress.org/extend/plugins/active-directory-integration/) plugin.

### Can I show/retrieve members of a specific AD group? ###

Yes, that is part of the core functionality of this plugin. You can easily filter the list of retrieved users with a specific AD group.

### Can I show/retrieve members of multiple AD groups together? ###

Not at this time. You can only provide one group at a time to filter the results.

### Can I display a single user with this plugin? ###

Yes, you can provide a username (samaccountname or userPrincipalName) to retrieve and display a single user from active directory.

### How do I use the output builder? ###

Documentation for the output builder can be found on [the official plugin page](http://plugins.ten-321.com/active-directory-employee-list/). The same documentation can be found by clicking the "More info" link under the output builder field on the plugin's options page.

### How do I know which Active Directory fields might be available? ###

Following is a list of standard Active Directory fields of which I am aware (I used [a list from a company called Computer Performance](http://www.computerperformance.co.uk/Logon/LDAP_attributes_active_directory.htm) to compile this list):

* cn - Common name - First name and last name together
* description - Full text description of user/group
* displayname - The name that should be displayed as the user's name
* dn - The pre-formatted user string used to bind to active directory
* givenname - The user's first name
* name - Should be the same as CN
* samaccountname - The unique user ID of the user (generally the login name)
* sn - The user's last name
* userprincipalname - A unique user ID, complete with domain, used for logging in
* mail - The user's email address
* mailnickname - The username portion of the user's email address
* c - Country or region
* company - The name of the user's company
* department - The name of the user's department in the company
* homephone - The user's home telephone number
* l - The physical location (city) of the user
* location - The computer location (??) of the user?
* manager - The user's boss or manager
* mobile - The user's mobile phone number
* ou - Organizational unit
* postalcode - ZIP code
* st - State, province or county
* streetaddress - First line of postal address
* telephonenumber - Office phone number

### I got a blank white screen, or only part of the page loaded; what does that mean? ###

That most likely means that too much information was retrieved and formatted at once by the plugin. Try retrieving/displaying fewer fields and/or filtering the list of users with a specific Active Directory group to see if that solves the issue. I have not yet implemented pagination in this plugin, so there is currently no way to limit the number of users it attempts to retrieve and display at once.

### It doesn't seem to be retrieving the complete list I expected ###

Unfortunately, there is [a documented issue with PHP](http://bugs.php.net/bug.php?id=42060) that it doesn't allow "paging" of query results. Therefore, it will only retrieve as many users as your Active Directory is configured to allow on a single "page" (appears to be 1500 by default).

## Screenshots ##

###1. Part of the Active Directory server/connection settings as shown in the Site Admin area of a multisite installation.
###
![Part of the Active Directory server/connection settings as shown in the Site Admin area of a multisite installation.
](https://s.w.org/plugins/active-directory-employee-list/screenshot-1.png)

###2. Options for modifying what information is retrieved from the AD server.
###
![Options for modifying what information is retrieved from the AD server.
](https://s.w.org/plugins/active-directory-employee-list/screenshot-2.png)

###3. Part of the output (formatting) settings.
###
![Part of the output (formatting) settings.
](https://s.w.org/plugins/active-directory-employee-list/screenshot-3.png)

###4. The widget control.
###
![The widget control.
](https://s.w.org/plugins/active-directory-employee-list/screenshot-4.png)


## Changelog ##

### 0.3 ###

* Fixed issue with custom ports (custom ports weren't used at all in previous versions because of limitations in adLDAP class)
* Added conditional to make sure adLDAPE class is not declared multiple times
* Fixed PHP incompatibility issue (syntax error - line 65)
* Fixed issue with saving options in non-network applications
* Fixed minor bugs
* Split widget into 2 separate widgets (one for single employees and a separate one for lists of employees)
* Added some preset output builder values for widgets
* Fixed error/warning messages about enqueuing scripts/styles when WP_DEBUG was turned on
* Added `max_results` parameter to shortcode to override LDAP option

### 0.2.1a ###

* This is the first stable version of the plugin
* Changed the text input for "fields to show" to a multiple select element on the options page
* Added documentation for the output builder

### 0.2a ###

* This version is the same as 0.2.1a. It was created because of a mix-up with the stable tags for the plugin.

### 0.1a ###

* This is the first version. No documented changes have occurred yet.

## Upgrade Notice ##

### 0.3 ###

* Major bugfixes (including ability to save options in single WP install)

### 0.2.1a ###

* Version 0.1a was never intended to be public. If you downloaded and installed it, please upgrade to 0.2.1a, as that is the first "stable" version of the plugin.

## To Do ##

* Implement pagination
* Provide a work-around for the PHP "paging" error
* Investigate allowing results to be filtered by multiple groups
* Investigate allowing results to be filtered by multiple usernames
