module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    criticalcss: {
        home: {
          options:  {
            outputfile : 'css/critical/critical-home.css',
            filename : 'style.css',
            url : 'http://localhost:8888/tidyrepo'
          }
        },

        content: {
          options: {
            outputfile : 'css/critical/critical-content.css',
            filename : 'style.css',
            url: 'http://localhost:8888/tidyrepo/coming-soon/'
          }
        }
    },
    autoprefixer: {
      css: {
        options: {
          diff: true
        },
        src: 'style.css',
        dest: 'style.css'
      }
    },
    svgstore: {
      options: {
        prefix : 'shape-',
        svg: {
          id : 'svgs'
        },
        cleanup: ['fill']
      },
      default : {
          files: {
            'svgs/svg-defs.svg': ['svgs/individual/*.svg'],
          }
        },

      findaplugin : {
          files: {
            'svgs/svg-findaplugin.svg': ['svgs/findaplugin/*.svg'],
          }
        }
      }
  });
  grunt.loadNpmTasks('grunt-criticalcss');
  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-svgstore');
  grunt.registerTask('default', ['criticalcss']);
  grunt.registerTask('svg', ['svgstore']);

};