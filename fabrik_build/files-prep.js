var libxmljs = require('libxmljs'),
    fs = require('fs-extra'),
    moment = require('moment'),
    buildConfig = require('./build-config.js');

module.exports = function (grunt) {
    jPlugins(grunt);
    fabrikPlugins(grunt);
    fabrikModules(grunt);
    component(grunt);
};

/**
 * Update Fabrik plugin/component/module XML file properties
 * @param path
 * @param grunt
 */
var updateAFile = function (path, grunt) {

    try {
        if (!fs.statSync(path).isFile()) {
            console.log('not a file');
            return;
        }

        var version = grunt.config.get('pkg.version');
        var date = new Date(),
            xml;

        var createDate = moment().format('MMMM YYYY');
        xml = fs.readFileSync(path);
        xmlDoc = libxmljs.parseXmlString(xml.toString());
        xmlDoc.get('//creationDate').text(createDate);
        xmlDoc.get('//copyright').text('Copyright (C) 2005-' + date.getFullYear() + ' Media A-Team, Inc. - All rights reserved.');
        xmlDoc.get('//version').text(version);

        var ext = xmlDoc.get('//extension');
        var attrs = ext.attrs();
        var newAttrs = {}
        for (var i = 0; i < attrs.length; i++) {
            k = attrs[i].name();
            var v = attrs[i].value();
            newAttrs[k] = v;
        }
        newAttrs.version = grunt.config.get('jversion')
        xmlDoc.get('//extension').attr(newAttrs);
        try {
            fs.writeFileSync(path, xmlDoc.toString());
        } catch (err) {
            console.error(err);
        }
    } catch (err) {
        console.error(err);
    }
};

var jPlugins = function (grunt) {
    var path;
    for (var p in buildConfig.plugins) {
        for (var i = 0; i < buildConfig.plugins[p].length; i++) {
            var plg = buildConfig.plugins[p][i];
            path = './' + plg.path + '/' + plg.element + '.xml';
            updateAFile(path, grunt);
        }
    }
};

var fabrikPlugins = function (grunt) {
    var folders = buildConfig.pluginFolders;
    for (var i = 0; i < folders.length; i++) {
        var pluginPath = './plugins/fabrik_' + folders[i];
        if (fs.lstatSync(pluginPath).isDirectory()) {
            var plugins = fs.readdirSync(pluginPath);
            for (var j = 0; j < plugins.length; j++) {

                if (fs.lstatSync(pluginPath + '/' + plugins[j]).isDirectory()) {
                    var path = pluginPath + '/' + plugins[j] + '/' + plugins[j] + '.xml';
                    updateAFile(path, grunt);
                }
            }
        }
    }
};

var fabrikModules = function (grunt) {
    for (var i = 0; i < buildConfig.modules.length; i++) {
        var mod = buildConfig.modules[i];
        var path = mod.path + '/' + mod.element + '.xml';
        updateAFile(path, grunt);
    }

};

var component = function (grunt) {
    updateAFile('administrator/components/com_fabrik/pkg_fabrik.xml', grunt);
    updateAFile('administrator/components/com_fabrik/pkg_fabrik_sink.xml', grunt);
    updateAFile('administrator/components/com_fabrik/fabrik.xml', grunt);
}