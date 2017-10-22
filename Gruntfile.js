/* jshint node: true */
module.exports = function (grunt) {
	'use strict';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		'ftp-deploy': {
			build: {
				auth: {
					host: 'thenafdev.obblm.com',
					port: 21,
					authKey: 'key1'
				},
				src: '.',
				dest: 'public_html',
				exclusions: ['.gitignore', 
                    '.travis.yml', 
                    'Gruntfile.js', 
                    'notes.txt', 
                    'README.md', 
                    'package.json', 
                    '.ftppass', 
                    'LICENSE', 
                    '.git', 
                    'node_modules', 
                    'test',
                    'install.php',   // It won't run if install.php is on the server.
                    'settings.php',  // Don't overwrite the database settings with the development ones!
                    'settings_modules.php',  // Don't overwrite the module settings with the development ones!
                    'localsettings/*.php'
                    ]
			}
		}
	});

	grunt.loadNpmTasks('grunt-ftp-deploy');

	grunt.registerTask('test', []);
	grunt.registerTask('deploy', ['create-ftp-file', 'ftp-deploy']);
	
	grunt.registerTask('create-ftp-file', 'Create an authentication file for FTP', function() {
		var ftpUsername = grunt.option('ftpUsername'),
			ftpPassword = grunt.option('ftpPassword');		
		
		var contents = '{"key1":{"username":"' + ftpUsername + '", "password":"' + ftpPassword + '"}}';
			
		// Create a file to supply the authentication parameters to the deployment
		grunt.file.write('.ftppass', contents);
	});
};