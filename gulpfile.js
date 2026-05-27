var browserSync = require('browser-sync').create(),
    gulp = require('gulp'),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCSS = require('gulp-clean-css'),
    include = require('gulp-include'),
    eslint = require('gulp-eslint'),
    isFixed = require('gulp-eslint-if-fixed'),
    babel = require('gulp-babel'),
    rename = require('gulp-rename'),
    sass = require('gulp-sass'),
    scsslint = require('gulp-scss-lint'),
    uglify = require('gulp-uglify'),
    merge = require('merge');


var configLocal = require('./gulp-config.json'),
    configDefault = {
      src: {
        scssPath: './src/scss',
        jsPath:   './src/js'
      },
      dist: {
        cssPath:  './static/css',
        jsPath:   './static/js',
        fontPath: './static/fonts'
      },
      devPath: './dev',
      packagesPath: './node_modules',
      sync: false,
      syncTarget: 'http://localhost/'
    },
    config = merge(configDefault, configLocal);

//
// CSS
//

// Base linting function
function lintSCSS(src) {
  return gulp.src(src)
    .pipe(scsslint({
      'maxBuffer': 400 * 1024  // default: 300 * 1024
    }));
}

// Lint all theme scss files
gulp.task('scss-lint-theme', function() {
  return lintSCSS(config.src.scssPath + '/*.scss');
});

// Base SCSS compile function
function buildCSS(src, dest) {
  dest = dest || config.dist.cssPath;

  return gulp.src(src)
    .pipe(sass({
      includePaths: [config.src.scssPath, config.packagesPath]
    })
      .on('error', sass.logError))
    .pipe(cleanCSS())
    .pipe(autoprefixer({
      // Supported browsers added in package.json ("browserslist")
      cascade: false
    }))
    .pipe(rename({
      extname: '.min.css'
    }))
    .pipe(gulp.dest(dest))
    .pipe(browserSync.stream());
}

// Compile theme stylesheet
gulp.task('scss-build-theme', function() {
  return buildCSS(config.src.scssPath + '/style.scss');
});

// All theme css-related tasks
gulp.task('css', gulp.series('scss-lint-theme', 'scss-build-theme'));


//
// Rerun tasks when files change
//
gulp.task('watch', function() {
  if (config.sync) {
    browserSync.init({
        proxy: {
          target: config.syncTarget
        }
    });
  }

  gulp.watch(config.src.scssPath + '/**/*.scss', gulp.series('css'));
  gulp.watch(config.src.jsPath + '/**/*.js', gulp.series('js')).on('change', browserSync.reload);
  gulp.watch('./**/*.php').on('change', browserSync.reload);
});


//
// Default task
//
gulp.task('default', gulp.series('components', 'css', 'js'));
