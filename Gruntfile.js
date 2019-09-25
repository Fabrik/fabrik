var fs = require('fs-extra'),
	Client = require('ftp'),
	Promise = require('bluebird'),
	archiver = require('archiver'),
	updateServer = require('./fabrik_build/update-server.js'),
	filesPrep = require('./fabrik_build/files-prep.js'),
	shell = require('shelljs'),
	rimraf = require('rimraf'),
	replace = require('replace'),
	buildConfig = require('./fabrik_build/build-config.js'),
    gruntLegacyUtil = require('grunt-legacy-util'),
	gruntLeineending = require('grunt-lineending'),
	zipPromises = [],
	done;
fs = Promise.promisifyAll(fs);

module.exports = function (grunt) {

    grunt.util.linefeed = '\n';

    // Project configuration.
	grunt.initConfig({
		pkg   : grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> */\n'
            },

			all: {
				files: grunt.file.expandMapping(
					[
						'./plugins/fabrik_*/*/*.js',
						'!./plugins/fabrik_*/**/*-min.js',
						'./plugins/fabrik_element/fileupload/lib/plupload/js/*.js',
						'!./plugins/fabrik_element/fileupload/lib/plupload/js/*-min.js',
						'./media/com_fabrik/js/*.js',
						'!./media/com_fabrik/js/*-min.js',
						'!/media/com_fabrik/js/**',
						'./media/com_fabrik/js/lib/datejs/**/*.js',
						'!./media/com_fabrik/js/lib/datejs/**/*-min.js',
						'./administrator/components/com_fabrik/models/fields/*.js',
						'!./administrator/components/com_fabrik/models/fields/*-min.js',
						'./administrator/components/com_fabrik/views/**/*.js',
						'!./administrator/components/com_fabrik/views/**/*-min.js'
					],
					'./plugins/fabrik_*/*/*.js',
					{
						rename: function (destBase, destPath) {

							if (destPath.indexOf('media/com_fabrik/js') !== -1) {
								// Put these files in their own folder
								return destPath.replace('/js/', '/js/dist/');
							} else {

								return destPath.replace('.js', '-min.js');
							}

						}
					}
				)
			}
		},

        lineending: {
            all: {
                options: {
                    overwrite: true,
                    eol: 'crlf'
                },
                files: [{
                	expand: true,
                    src: [
                        './plugins/fabrik_*/**/*-min.js',
						'./plugins/fabrik_element/fileupload/lib/plupload/js/*-min.js',
						'./media/com_fabrik/js/dist/*-min.js',
						'./media/com_fabrik/js/dist/lib/datejs/**/*-min.js',
						'./administrator/components/com_fabrik/models/fields/*-min.js',
						'./administrator/components/com_fabrik/views/**/*-min.js'
					],
					dest: ''
				}]

            }
        },

		compress: {
			main: {
				options: {
					archive: grunt.config('config.version') + '.zip'
				},
				files  : [
					{src: ['path/*'], dest: 'internal_folder/', filter: 'isFile'}, // includes files in path
					{src: ['path/**'], dest: 'internal_folder2/'}, // includes files in path and its subdirs
				]
			}
		},

		prompt: {
			target: {
				options: {
					questions: [
						{
							config : 'pkg.version', // arbitrary name or config for any other grunt task
							type   : 'input', // list, checkbox, confirm, input, password
							message: 'Fabrik version:', // Question to ask the user, function needs to return a string,
							default: grunt.config('pkg.version') // default value if nothing is entered
						},
						{
							config : 'jversion',
							type   : 'input',
							message: 'Joomla target version #',
							default: '3'
						},
						{
							config : 'live',
							type   : 'confirm',
							message: 'Deployment to live server',
							default: false
						},
						{
							config : 'changelog',
							type   : 'confirm',
							message: 'Git changelog',
							default: false
						},
						{
							config : 'upload.zips',
							type   : 'confirm',
							message: 'Upload Zips to update server?',
							default: false
						},
						{
							config : 'upload.xml',
							type   : 'confirm',
							message: 'Upload update XML files to update server?',
							default: false
						},
						{
							config : 'phpdocs.create',
							type   : 'confirm',
							message: 'Build PHP Docs?'
						},
						{
							config : 'phpdocs.upload',
							type   : 'confirm',
							message: 'Upload PHP docs to fabrikar.com'
						},
					]
				}
			}
		}
	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-lineending');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-prompt');

	grunt.registerTask('js', ['uglify','lineending']);

	// Default task(s).
	grunt.registerTask('default', ['prompt', 'fabrik']);

	grunt.registerTask('fabrik', 'testing build', function () {
		var version = grunt.config.get('pkg.version'),
			p, i, j, config, dest,
			pluginTypes = ['fabrik_cron', 'fabrik_element', 'fabrik_list',
				'fabrik_validationrule', 'fabrik_visualization', 'fabrik_form'],
			simpleGit = require('simple-git')('./');
		done = this.async();

		grunt.log.writeln('Building fabrik......' + version);
		filesPrep(grunt);
		refreshFiles();
		component(version, grunt);
		console.log('-- Component built');
		updateServer(grunt);
		console.log('-- Update server files created');

		fs.mkdirsSync('fabrik_build/output/plugins');
		fs.mkdirsSync('fabrik_build/output/pkg_fabrik/packages');
		fs.mkdirsSync('fabrik_build/output/pkg_fabrik_sink/packages');

		console.log('-- Package folders created');

		for (p = 0; p < pluginTypes.length; p++) {
			var pluginFolder = 'fabrik_build/output/plugins/' + pluginTypes[p],
				sourceFolder = 'plugins/' + pluginTypes[p],
				files = fs.readdirSync(sourceFolder);

			// Copy folders ignoring any symlinked folders.
			for (j = 0; j < files.length; j++) {
				var file = sourceFolder + '/' + files[j];
				var stat = fs.lstatSync(file);
				if (stat.isDirectory() && !stat.isSymbolicLink(file)) {
					fs.mkdirsSync(pluginFolder + '/' + file);
					fs.copySync(file, pluginFolder + '/' + file);
				}
			}

			var plugins = fs.readdirSync(pluginFolder);

			for (i = 0; i < plugins.length; i++) {
				dest = 'fabrik_build/output/pkg_fabrik_sink/packages/plg_' +
					pluginTypes[p] + '_' + plugins[i] + '_' + version + '.zip';
				zipPromises.push(zipPlugin(pluginFolder + '/' + plugins[i], dest));
			}
		}
		console.log('-- Fabrik Plugin folders created');

		for (p in buildConfig.plugins) {
			if (buildConfig.plugins.hasOwnProperty(p)) {
				for (i = 0; i < buildConfig.plugins[p].length; i++) {
					config = buildConfig.plugins[p][i];
					dest = 'fabrik_build/output/pkg_fabrik_sink/packages/' +  config.fileName.replace('{version}', version);
					zipPromises.push(zipPlugin(config.path, dest));
				}
			}

		}
		for (i = 0; i < buildConfig.modules.length; i++) {
			config = buildConfig.modules[i];
			dest = 'fabrik_build/output/pkg_fabrik_sink/packages/' +  config.fileName.replace('{version}', version);
			zipPromises.push(zipPlugin(config.path, dest));
		}
        for (i = 0; i < buildConfig.libraries.length; i++) {
            config = buildConfig.libraries[i];
            dest = 'fabrik_build/output/pkg_fabrik_sink/packages/' +  config.fileName.replace('{version}', version);
            zipPromises.push(zipPlugin(config.path, dest));
        }

		buildPHPDocs(grunt);
		uploadPHPDocs(grunt);

		console.log('You will need to run: subs.fabrikar.com/fabrik_downloads_update.php ' +
			'to update the db download entries');

		simpleGit.tags(function (err, tags) {
			console.log('git tags err: ' + err);
			if (tags.all.indexOf(version) !== -1) {
				// A previous tag with the same version exists - remove it and reset latest version #
				shell.exec('git tag -d ' + version);
				tags.latest = tags.all[tags.all.length - 2];
			}

			if (grunt.config.get('changelog')) {
				changelog(tags.latest);
			}

			if (grunt.config.get('live')) {
				// Add the new tag
				simpleGit.addTag(version, function (err, res) {
					//    console.log(err, res);
				});
			}
		});
	});

};

/**
 * Build a zip
 * @param source
 * @param dest
 * @return promise
 */
var zipPlugin = function (source, dest) {

	return new Promise(function (resolve, reject) {
		var stat = fs.lstatSync(source);
		if (!stat.isDirectory()) {
			resolve();
		} else {

			var archive = archiver.create('zip', {});
			var output = fs.createWriteStream(dest);

			output.on('close', function () {
				console.log(dest + ': ' + archive.pointer() + ' total bytes');
				resolve();
			});

			archive.on('error', function (err) {
				console.error('ERROR MAKING ZIP:' + dest, err);
				reject();
			});

			archive.pipe(output);
			archive.directory(source, false);
			archive.finalize();
		}
	});
};

var buildPHPDocs = function (grunt) {
	console.log('todo: build php docs' + grunt.config('phpdocs.create'));
};

var uploadPHPDocs = function (grunt) {
	console.log('todo: uploadPHPDocs: ' + grunt.config('phpdocs.upload'));
};

var changelog = function (latest) {
	var result = shell.exec("git log --pretty=format:\"* %s (%an)\" 3.3.3..HEAD");
	fs.writeFileSync('fabrik_build/changelog.txt', result.stdout);
};

/**
 * Copy over the component files into the fabrik_build folder.
 */
var refreshFiles = function () {
	var tmpl;
	rimraf.sync('./fabrik_build/output/');
	fs.mkdirsSync('./fabrik_build/output/component/admin');
	fs.mkdirsSync('./fabrik_build/output/component/site/fabrikfeed');
	fs.mkdirsSync('./fabrik_build/output/component/site/pdf');
	fs.mkdirsSync('./fabrik_build/output/component/site/partial');
    fs.mkdirsSync('./fabrik_build/output/component/site/Document');
	fs.mkdirsSync('./fabrik_build/output/component/media');
	fs.mkdirsSync('./fabrik_build/output/library');


	fs.copySync('libraries/joomla/document/fabrikfeed', './fabrik_build/output/component/site/fabrikfeed');
	fs.copySync('libraries/joomla/document/pdf', './fabrik_build/output/component/site/pdf');
	fs.copySync('libraries/joomla/document/partial', './fabrik_build/output/component/site/partial');
    fs.copySync('libraries/src/Document/PartialDocument.php', './fabrik_build/output/component/site/Document/PartialDocument.php');
    fs.copySync('libraries/src/Document/PdfDocument.php', './fabrik_build/output/component/site/Document/PdfDocument.php');
    fs.copySync('libraries/src/Document/Renderer/Partial', './fabrik_build/output/component/site/Document/Renderer/Partial');
    fs.copySync('libraries/src/Document/Renderer/Pdf', './fabrik_build/output/component/site/Document/Renderer/Pdf');
    fs.copySync('libraries/fabrik/fabrik/Helpers', './fabrik_build/output/component/site/fabrik/fabrik/Helpers');

    // Library folder
	fs.copySync('libraries/fabrik', './fabrik_build/output/library');

	fs.copySync('administrator/components/com_fabrik/', './fabrik_build/output/component/admin', {
		'filter': function (f) {
			//console.log('admin file: ' + f);
			if (f.indexOf('\\com_fabrik\\update\\') !== -1) {
				console.log('*********' + f);
				return false;
			}
			return f.indexOf('.zip') === -1;
		}
	});
	fs.copySync('components/com_fabrik/', './fabrik_build/output/component/site', {
		'filter': function (f) {
			if (f.indexOf('.zip') !== -1) {
				return false;
			}
			return true;
		}
	});
	fs.copySync('components/com_fabrik/js/index.html', './fabrik_build/output/component/site/js/index.html');
	fs.copySync('media/com_fabrik/', './fabrik_build/output/component/media/com_fabrik', {
		'filter': function (f) {
			return f.indexOf('.zip') === -1;
		}
	});

	fs.removeSync('./fabrik_build/output/component/site/views/form/tmpl');
	fs.removeSync('./fabrik_build/output/component/site/views/form/tmpl25');
	fs.removeSync('./fabrik_build/output/component/site/views/list/tmpl25');
	fs.removeSync('./fabrik_build/output/component/site/views/list/tmpl');

	console.log('copying list templates');
	// explicitly include list 2.5 templates
	//fs.mkdirsSync('./fabrik_build/output/component/site/views/list/tmpl25');
	//fs.copySync('./components/com_fabrik/views/list/tmpl25/default.xml',
	//    './fabrik_build/output/component/site/views/list/tmpl25/default.xml');
	//fs.copySync('./components/com_fabrik/views/list/tmpl25/_advancedsearch.php',
	//    './fabrik_build/output/component/site/views/list/tmpl25/_advancedsearch.php');
	//var tmpls = ['admin', 'adminmodule', 'bluesky', 'default', 'div'];
	//for (var i = 0; i < tmpls.length; i++) {
	//    tmpl = tmpls[i];
	//    fs.mkdirsSync('./fabrik_build/output/component/site/views/list/tmpl25/' + tmpl);
	//    fs.copySync('./components/com_fabrik/views/list/tmpl25/' + tmpl,
	//        './fabrik_build/output/component/site/views/list/tmpl25/' + tmpl);
	//}

	// explicitly include 3.0 list templates
	fs.mkdirsSync('./fabrik_build/output/component/site/views/list/tmpl');
	fs.copySync('./components/com_fabrik/views/list/tmpl/default.xml',
		'./fabrik_build/output/component/site/views/list/tmpl/default.xml');
	fs.copySync('./components/com_fabrik/views/list/tmpl/_advancedsearch.php',
		'./fabrik_build/output/component/site/views/list/tmpl/_advancedsearch.php');
	tmpls = ['bootstrap', 'div'];
	for (i = 0; i < tmpls.length; i++) {
		tmpl = tmpls[i];
		fs.mkdirsSync('./fabrik_build/output/component/site/views/list/tmpl/' + tmpl);
		fs.copySync('./components/com_fabrik/views/list/tmpl/' + tmpl,
			'./fabrik_build/output/component/site/views/list/tmpl/' + tmpl);
	}

	console.log('copying form templates');
	// explicitly include 2.5 form templates
    /*
     fs.mkdirsSync('./fabrik_build/output/component/site/views/form/tmpl25');
     fs.copySync('./components/com_fabrik/views/form/tmpl25/default.xml',
     './fabrik_build/output/component/site/views/form/tmpl25/default.xml');
     tmpls = ['admin', 'bluesky', 'contacts_custom', 'default', 'default_rtl', 'mint', 'no-labels', 'tabs'];

     for (i = 0; i < tmpls.length; i++) {
     tmpl = tmpls[i];
     fs.mkdirsSync('./fabrik_build/output/component/site/views/form/tmpl25/' + tmpl);
     fs.copySync('./components/com_fabrik/views/form/tmpl25/' + tmpl,
     './fabrik_build/output/component/site/views/form/tmpl25/' + tmpl,
     {
     'filter': function (f) {
     if (f.indexOf('custom_css.php') !== -1) {
     return false;
     }
     return true;
     }
     });
     }
     */

	// explicitly include 3.0 form templates
	fs.mkdirsSync('./fabrik_build/output/component/site/views/form/tmpl');
	fs.copySync('./components/com_fabrik/views/form/tmpl/default.xml',
		'./fabrik_build/output/component/site/views/form/tmpl/default.xml');

	tmpls = ['bootstrap', 'bootstrap_tabs'];
	for (i = 0; i < tmpls.length; i++) {
		tmpl = tmpls[i];
		fs.mkdirsSync('./fabrik_build/output/component/site/views/form/tmpl/' + tmpl);
		fs.copySync('./components/com_fabrik/views/form/tmpl/' + tmpl,
			'./fabrik_build/output/component/site/views/form/tmpl/' + tmpl,
			{
				'filter': function (f) {
					if (f.indexOf('custom_css.php') !== -1) {
						return false;
					}
					return true;
				}
			});
	}
	console.log('copying drivers');

	// Copy over the database drivers
	fs.mkdirsSync('./fabrik_build/output/component/site/dbdriver');
	fs.copySync('components/com_fabrik/js/index.html', './fabrik_build/output/component/site/dbdriver/index.html');

	// J3.0 db drivers
	fs.mkdirsSync('./fabrik_build/output/component/site/driver');
	fs.copySync('./libraries/joomla/database/driver/mysql_fab.php',
		'./fabrik_build/output/component/site/driver/mysql_fab.php');
	fs.copySync('./libraries/joomla/database/driver/mysqli_fab.php',
		'./fabrik_build/output/component/site/driver/mysqli_fab.php');
	fs.copySync('./libraries/joomla/database/driver/pdomysql_fab.php',
		'./fabrik_build/output/component/site/driver/pdomysql_fab.php');
	fs.mkdirsSync('./fabrik_build/output/component/site/query');
	fs.copySync('./libraries/joomla/database/query/mysql_fab.php',
		'./fabrik_build/output/component/site/query/mysql_fab.php');
	fs.copySync('./libraries/joomla/database/query/mysqli_fab.php',
		'./fabrik_build/output/component/site/query/mysqli_fab.php');
	fs.copySync('./libraries/joomla/database/query/pdomysql_fab.php',
		'./fabrik_build/output/component/site/query/pdomysql_fab.php');
};

var  library = function (version, grunt) {
	console.log('-- Creating library zip');
	zipPromises.push(zipPlugin('fabrik_build/library/fabrik',
		'fabrik_build/output/lib_fabrik_' + version + '.zip'));
    /*
     zipPromises.push(zipPlugin('fabrik_build/library/',
     'fabrik_build/output/pkg_fabrik/packages/lib_fabrik_' + version + '.zip'));
     */
};

var component = function (version, grunt) {
	// Need to move the package.xml file out of the component to avoid nasties
	fs.move(
		'./fabrik_build/output/component/admin/fabrik.xml',
		'./fabrik_build/output/component/fabrik.xml',
		function () {
			fs.move(
				'./fabrik_build/output/admin/com_fabrik.manifest.class.php',
				'./fabrik_build/output/component/com_fabrik.manifest.class.php',
				function () {
					fs.move('' +
						'./fabrik_build/output/component/admin/com_fabrik.manifest.class.php',
						'./fabrik_build/output/component/com_fabrik.manifest.class.php',
						function () {
							fs.move(
								'./fabrik_build/output/component/admin/pkg_fabrik.xml',
								'./fabrik_build/output/pkg_fabrik/pkg_fabrik.xml',
								function () {
									fs.move(
										'./fabrik_build/output/component/admin/pkg_fabrik_sink.xml',
										'./fabrik_build/output/pkg_fabrik_sink/pkg_fabrik_sink.xml',
										function () {
											zipPromises.push(
												zipPlugin(
													'fabrik_build/output/component/',
													'fabrik_build/output/pkg_fabrik_sink/packages/com_fabrik_' + version + '.zip'
												)
											);
											//library(version, grunt);
											packages(version, grunt);
										}
									);
								}
							);
						}
					);
				}
			);
		}
	);
};

var packages = function (version, grunt) {
	// Run once all the promises have finished
	Promise.settle(zipPromises)
		.then(function () {
			console.log('-- Zip promises done');
			console.log('-- Start package build');

			var i, zips = buildConfig.corePackageFiles;
			replace({
				regex: '{version}',
				replacement: version,
				paths: ['./fabrik_build/output/pkg_fabrik/pkg_fabrik.xml',
					'./fabrik_build/output/pkg_fabrik_sink/pkg_fabrik_sink.xml'],
				recursive: false,
				silent: false
			});
			// Copy files from sink to pkg
			for (i = 0; i < zips.length; i++) {
				zips[i] = zips[i].replace('{version}', version);
				try {
					fs.copySync('fabrik_build/output/pkg_fabrik_sink/packages/' + zips[i],
						'fabrik_build/output/pkg_fabrik/packages/' + zips[i]);
				} catch (err) {
					console.error(err.message);
				}

			}
			var promises = [
				zipPlugin('fabrik_build/output/pkg_fabrik_sink', 'fabrik_build/output/pkg_fabrik_sink_' + version + '.zip'),
				zipPlugin('fabrik_build/output/pkg_fabrik', 'fabrik_build/output/pkg_fabrik_' + version + '.zip'),
				zipPlugin('fabrik_build/output/library', 'fabrik_build/output/lib_fabrik_' + version + '.zip')
			];
			Promise.settle(promises)
				.then(function () {
					ftp(grunt, version);
				});

		});
};

var ftp = function (grunt, version) {

	var c = new Client();
	var config = grunt.file.readJSON('private.json').ftp;
	var promises = [], i;

	c.on('ready', function () {
		console.log('connected');
		if (grunt.config.get('upload.zips')) {
			promises.push(ftpPromise(c, 'fabrik_build/output/pkg_fabrik_' + version + '.zip',
				'/public_html/media/downloads/pkg_fabrik_' + version + '.zip'));
			promises.push(ftpPromise(c, 'fabrik_build/output/pkg_fabrik_sink_' + version + '.zip',
				'/public_html/media/downloads/pkg_fabrik_sink_' + version + '.zip'));
			promises.push(ftpPromise(c, 'fabrik_build/output/lib_fabrik_' + version + '.zip',
				'/public_html/media/downloads/lib_fabrik_' + version + '.zip'));

			var plugins = fs.readdirSync('fabrik_build/output/pkg_fabrik_sink/packages');

			for (i = 0; i < plugins.length; i++) {
				promises.push(ftpPromise(c, 'fabrik_build/output/pkg_fabrik_sink/packages/' + plugins[i],
					'/public_html/media/downloads/' + plugins[i]));
			}
		}
		if (grunt.config.get('upload.xml')) {
			var xmlFiles = fs.readdirSync('fabrik_build/output/updateserver');

			for (i = 0; i < xmlFiles.length; i++) {
				promises.push(ftpPromise(c, 'fabrik_build/output/updateserver/' + xmlFiles[i],
					'/public_html/update/fabrik31/' + xmlFiles[i]));
			}
		}
		Promise.settle(promises)
			.then(function () {
				c.end();
				done();
			});

	});
	// Connect
	c.connect(config);

};

var ftpPromise = function (c, source, dest) {
	return new Promise(function (resolve, reject) {
		console.log('starting ftp:' + dest);
		c.put(source, dest, function (err) {
			if (err) {
				reject();
			}
			console.log('uploaded: ' + dest);
			resolve();
		});
	});
};
