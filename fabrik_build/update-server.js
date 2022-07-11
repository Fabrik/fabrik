var fs = require('fs-extra');
var Promise = require('bluebird');
Promise.promisifyAll(fs);
var buildConfig = require('./build-config.js');
var libxmljs = require('libxmljs'),
    mkdirp = require('mkdirp'),
    updateDir = './fabrik_build/output/updateserver/',
    packageList = updateDir + 'package_list.xml',
    extensions = [];


module.exports = function (grunt) {
    mkdirp.sync(updateDir);
    fs.copySync('administrator/components/com_fabrik/update/fabrik31', updateDir);
    jPlugins(grunt);
    console.log('-- Update Server: J Plugins created');
    fabrikPlugins(grunt);
    console.log('-- Update Server: Fabrik Plugins created');
    fabrikModules(grunt);
    console.log('-- Update Server: Fabrik Modules created');
    component(grunt);
    console.log('-- Update Server: Component created');
	library(grunt);
	console.log('-- Update Server: Library created');
    makePackageList(extensions);
    console.log('-- Update Server: Package list created');
    // Copy back
    fs.copySync(updateDir, 'administrator/components/com_fabrik/update/fabrik31');
};

/**
 * Build the update server's XML file that describes where each individual plugin etc xml
 * manifest files are located
 */
var makePackageList = function (extensions) {
    var xmlDoc = libxmljs.Document(),
        i, node,
        root = xmlDoc.node('extensionset');
    root.attr({'description': 'Fabrik', 'name': 'Fabrik'});

    for (i = 0; i < extensions.length; i++) {
        node = libxmljs.Element(xmlDoc, 'extension');
        node.attr(extensions[i].$);
        root.addChild(node);
    }
    try {
        fs.writeFileSync(packageList, xmlDoc.toString());
    } catch (err) {
        console.log(err);
    }

    return xmlDoc;
};

var writeXml = function (xmlFile, props, version) {
    var xmlDoc = buildXml(xmlFile, props, version);
    try {
        fs.writeFileSync(xmlFile, xmlDoc.toString());
    } catch (err) {
        console.log(err);
    }
};

var buildXml = function (xmlFile, props, version) {
    var xml, xmlDoc, update, elem;

    try {
        xml = fs.readFileSync(xmlFile);
        xmlDoc = libxmljs.parseXmlString(xml.toString());
    } catch (err) {
        console.log('no xml file found for ', xmlFile);
        xmlDoc = libxmljs.Document();
        xmlDoc.node('updates');
    }

    // Check for existing entry - if found then delete it..
    var remove = xmlDoc.find("//version[text()='" + version + "']/..");
    for (var i = 0; i < remove.length; i++) {
        remove[i].remove();
    }

    elem = xmlDoc.root();
    update = libxmljs.Element(xmlDoc, 'update');
    update = map(xmlDoc, props, update);
    elem.addChild(update);
    return xmlDoc;
};

var map = function (xmlDoc, props, update) {
    var name, txt, child, key;
    if (Object.prototype.toString.call(props) === '[object Array]') {
        // todo...
    } else {
        for (key in props) {
            if (props.hasOwnProperty(key)) {
                if (typeof(props[key]) !== 'object') {
                    name = libxmljs.Element(xmlDoc, key, props[key]);
                    update.addChild(name);
                } else {
                    if (props[key].$) {
                        txt = props[key]['_'] ? props[key]['_'] : '';
                        name = libxmljs.Element(xmlDoc, key, txt);
                        name.attr(props[key].$);
                        update.addChild(name);
                    } else {
                        child = libxmljs.Element(xmlDoc, key);
                        update.addChild(child);
                        // Prob wont work for deep nested stuff... but ok for downloads
                        map(xmlDoc, props[key], child);
                    }
                }
            }

        }
    }

    return update;
};

var component = function (grunt) {
    var version = grunt.config.get('pkg.version'),
        jversion = grunt.config.get('jversion'),
        xmlFile,
        props = {
            'name'          : 'Fabrik',
            'description': 'Fabrik Component',
            'element'    : 'com_fabrik',
            'type'       : 'component',
            'version'    : version,
            'downloads'  : {
                'downloadurl': {
                    '$': {'type': 'full', 'format': 'zip'},
                    '_': 'http://fabrikar.com/media/downloads/com_fabrik_' + version + '.zip'
                }
            },
            'maintainer' : 'Fabrikar.com',
            'maintainerurl': 'http://fabrikar.com',
            'targetplatform': {
                '$': {
                    'name'   : 'joomla',
                    'version': jversion
                }
            }
        };
    extensions.push({
        '$': {
            'client'  : 'administrator',
            'name'    : 'fabrik',
            'element' : 'com_fabrik',
            'type'    : 'component',
            'folder'  : '',
            'version' : version,
            detailsurl: 'http://fabrikar.com/update/fabrik31/com_fabrik.xml'
        }
    });

    xmlFile = updateDir + 'com_fabrik.xml';
    writeXml(xmlFile, props, version);
};

var library = function (grunt) {
	var version = grunt.config.get('pkg.version'),
		jversion = grunt.config.get('jversion'),
		xmlFile,
		props = {
			'name'          : 'Fabrik',
			'description': 'Fabrik Library',
			'element'    : 'lib_fabrik',
			'type'       : 'library',
			'version'    : version,
			'downloads'  : {
				'downloadurl': {
					'$': {'type': 'full', 'format': 'zip'},
					'_': 'http://fabrikar.com/media/downloads/lib_fabrik_' + version + '.zip'
				}
			},
			'maintainer' : 'Fabrikar.com',
			'maintainerurl': 'http://fabrikar.com',
			'targetplatform': {
				'$': {
					'name'   : 'joomla',
					'version': jversion
				}
			}
		};
	extensions.push({
		'$': {
			'client'  : 'site',
			'name'    : 'fabrik',
			'element' : 'lib_fabrik',
			'type'    : 'library',
			'folder'  : '',
			'version' : version,
			detailsurl: 'http://fabrikar.com/update/fabrik31/lib_fabrik.xml'
		}
	});

	xmlFile = updateDir + 'lib_fabrik.xml';
	writeXml(xmlFile, props, version);
};


var fabrikModules = function (grunt) {
    console.log('.....fabrikModules......');
    var version = grunt.config.get('pkg.version'),
        jversion = grunt.config.get('jversion'),
        i, mod, props, xmlFile, client,
        updateFolder = grunt.config.get('pkg.config.live.downloadFolder');

    for (i = 0; i < buildConfig.modules.length; i++) {
        mod = buildConfig.modules[i];
        client = mod.client ? mod.client : 'site';
        props = {
            'name'          : mod.name,
            'description'   : mod.name,
            'element'       : mod.element,
            'type'          : 'module',
            'version'       : version,
            'client'        : client,
            'downloads'     : {
                'downloadurl': {
                    '$': {'type': 'full', 'format': 'zip'},
                    '_': updateFolder + mod.fileName.replace('{version}', version)
                }
            },
            'maintainer'    : 'Fabrikar.com',
            'maintainerurl' : 'http://fabrikar.com',
            'targetplatform': {
                '$': {
                    'name'   : 'joomla',
                    'version': jversion
                }
            }
        };
        extensions.push({
            '$': {
                'name'    : mod.name, 'element': mod.element, 'type': 'module', 'folder': '', 'version': version,
                'client': client,
                detailsurl: 'http://fabrikar.com/update/fabrik31/' + mod.xmlFile
            }
        });

        xmlFile = updateDir + mod.xmlFile;
        writeXml(xmlFile, props, version);
    }
};

var jPlugins = function (grunt) {
    var version = grunt.config.get('pkg.version'),
        jversion = grunt.config.get('jversion'),
        p, i, plg, props, xmlFile;
    for (p in buildConfig.plugins) {
        // Community builder plugins can't be installed via the update server.
        if (p === 'comprofiler') {
            continue;
        }
        for (i = 0; i < buildConfig.plugins[p].length; i++) {
            plg = buildConfig.plugins[p][i];

            props = {
                'name'          : plg.name,
                'description'   : plg.name,
                'element'       : plg.element,
                'type'          : 'plugin',
                'folder'        : p,
                'version'       : version,
                'downloads'     : {
                    'downloadurl': {
                        '$': {'type': 'full', 'format': 'zip'},
                        '_': grunt.config.get('pkg.config.live.downloadFolder') + plg.fileName.replace('{version}', version)
                    }
                },
                'maintainer'    : 'Fabrikar.com',
                'maintainerurl' : 'http://fabrikar.com',
                'targetplatform': {
                    '$': {
                        'name'   : 'joomla',
                        'version': jversion
                    }
                }
            };

            extensions.push({
                '$': {
                    'name'    : plg.name,
                    'element' : plg.element,
                    'type'    : 'plugin',
                    'folder'  : p.toLowerCase(),
                    'version' : version,
                    detailsurl: 'http://fabrikar.com/update/fabrik31/' + plg.xmlFile
                }
            });

            xmlFile = updateDir + plg.xmlFile;
            writeXml(xmlFile, props, version);
        }
    }
};

var fabrikPlugins = function (grunt) {
    var productName = grunt.config.get('pkg.name'),
        version = grunt.config.get('pkg.version'),
        jversion = grunt.config.get('jversion'),
        i, pluginPath, plugins, j, name, xmlFile, props,
        folders = buildConfig.pluginFolders;

    for (i = 0; i < folders.length; i++) {

        //
        var folder = 'plugins/fabrik_' + folders[i];
        var files = fs.readdirSync(folder);

        for (j = 0; j < files.length; j++) {
            var file = folder + '/' + files[j];
            var stat = fs.lstatSync(file);
            if (!stat.isSymbolicLink(file)) {
                fs.copySync(file, 'fabrik_build/output/' + file);
            }
        }

        //fs.copySync('plugins/fabrik_' + folders[i], 'fabrik_build/output/plugins/fabrik_' + folders[i]);
        pluginPath = 'fabrik_build/output/plugins/fabrik_' + folders[i];
        if (fs.lstatSync(pluginPath).isDirectory()) {
            console.log('----------> pluginPath: ' + pluginPath);
            plugins = fs.readdirSync(pluginPath);
            for (j = 0; j < plugins.length; j++) {

                var pluginDir = fs.lstatSync(pluginPath + '/' + plugins[j]);
                if (pluginDir.isDirectory() && !pluginDir.isSymbolicLink()) {
                    xmlFile = updateDir + '/plg_' + folders[i] + '_' + plugins[j] + '.xml';
                    name = productName + ' ' + folders[i] + ': ' + plugins[j],
                        element = 'plg_' + folders[i] + '_' + plugins[j],
                        folder = productName + '_' + folders[i];
                    props = {
                        'name'          : name,
                        'description'   : name,
                        'element'       : element,
                        'type'          : 'plugin',
                        'folder'        : folder,
                        'version'       : version,
                        'downloads'     : {
                            'downloadurl': {
                                '$': {'type': 'full', 'format': 'zip'},
                                '_': grunt.config.get('pkg.config.live.downloadFolder') + 'plg_' + productName.toLowerCase() + '_' + folders[i] + '_' + plugins[j] + '_' + version + '.zip'
                            }
                        },
                        'maintainer'    : 'Fabrikar.com',
                        'maintainerurl' : 'http://fabrikar.com',
                        'targetplatform': {
                            '$': {
                                'name'   : 'joomla',
                                'version': jversion
                            }
                        }
                    };

                    extensions.push({
                        '$': {
                            'name'    : name,
                            'element' : plugins[j],
                            'type'    : 'plugin',
                            'folder'  : folder.toLowerCase(),
                            'version' : version,
                            detailsurl: 'http://fabrikar.com/update/fabrik31/plg_' + folders[i] + '_' + plugins[j] + '.xml'
                        }
                    });

                    writeXml(xmlFile, props, version);
                }
            }
        }
    }
}