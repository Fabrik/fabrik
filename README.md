Fabrik is the Joomla application builder

Installation
================

[![Join the chat at https://gitter.im/Fabrik/fabrik](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Fabrik/fabrik?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

- Check out the files into your Joomla installations root directory
- Log into your Joomla administration control panel
- Go to Extensions > Extension Manager
- Click the 'Discover' link
- Click the 'Purge Cache' icon 
- Click the 'Discover' icon
- Select the Fabrik package and click the 'Install' icon

Enabling Plugins
================

Fabrik ships with several of the most common element and validation plugins, however to reduce installation package size not all available plugins are pre-installed.

All other element and validation plugins can be found by following these steps:

- Go to Extensions > Extension Manager
- Click the 'Discover' link
- Click the 'Purge Cache' icon 
- Click the 'Discover' icon
- Find the element or validation plugin you want to install
- Select it
- Click 'Install'
- Go to 'Manage' and make sure it is published

The element or validation plugin will then appear as an option when creating an element or validation.

Updating
================

Once you have installed Fabrik 3.x, if you want to stay up to date with the latest code updates between official releases, you can 'update from GitHub'.

Please see here for instructions on how to update from GitHub - [http://fabrikar.com/forums/index.php?wiki/update-from-github/](http://fabrikar.com/forums/index.php?wiki/update-from-github/).

Upgrading
================

At the time of an official release, please follow the steps here - [http://fabrikar.com/forums/index.php?wiki/upgrade-instructions/](http://fabrikar.com/forums/index.php?wiki/upgrade-instructions/)

Building
========

We have a Grunt build file which will create the Fabrik package for you, and place it in a folder /fabrik_build
The build relies on node-gpy, which depending on your operating system may require additional installations beyond node itself.
Please follow the instructions @ https://github.com/nodejs/node-gyp
 
Once done you should be able open a command prompt (best to run as administrator in Windows) and then do

> npm install
> Grunt

An additional tasks is available for minimising the javascript:

> Grunt js

Further Information
================

[Wiki](http://fabrikar.com/forums/index.php?wiki)  
[FAQ](http://fabrikar.com/forums/index.php?wiki/faq/)  
[Forum](http://fabrikar.com/forums/)  
[Things You Can Do](http://fabrikar.com/forums/index.php?wiki/things-you-can-do/)  
[Contribute Code](http://fabrikar.com/forums/index.php?wiki/contribute-code/)  
[Contribute Translation](http://fabrikar.com/forums/index.php?wiki/translations/)  