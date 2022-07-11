/**
 * User Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'element/databasejoin/databasejoin'], function (jQuery, FbDatabasejoin) {
    window.FbUser = new Class({
        Extends: FbDatabasejoin
    });
    return window.FbUser;
});