const gulp      = require('gulp');
const $         = require('gulp-load-plugins')();
const data      = require('json-file').read('./package.json').data;
const del       = require('del');

const src = 'src';
const dist = 'dist/' + data.name;

// clean dist folder
gulp.task('clean', () => {
    return del([dist]);
});


/**
 * Copy to Dist
 */
gulp.task('copy_to_dist', () => {
    // copy files to the dist folder
    return gulp.src(src + '/**/*')
        .pipe($.cached('copy_to_dist'))
        .pipe(gulp.dest(dist));
});


/**
 * Compile SCSS files
 */
gulp.task('sass', () => {
    return gulp.src([`${dist}/**/*.scss`, `!${dist}/assets/vendor/**/*`])
        .pipe($.cached('sass'))
        .pipe($.sass({
            outputStyle: 'compressed'
        }).on('error', $.sass.logError))
        .pipe($.autoprefixer({
            browsers: ['last 3 version', '> 1%']
        }))
        .pipe(gulp.dest(dist));
});
gulp.task('sass-clean', () => {
    return del(`${dist}/assets/**/*.scss`);
});


/**
 * Compile JS files
 */
gulp.task('js', () => {
    return gulp.src([`${dist}/assets/**/*.js`, `!${dist}/assets/vendor/**/*`])
        .pipe($.cached('js'))
        .pipe($.uglify({
            output: {
                comments: /^!/
            }
        }))
        .pipe(gulp.dest(`${dist}/assets`));
});


/**
 * Consistent Line Endings for non UNIX systems
 */
gulp.task('correct_lines_ending', () => {
    // copy files to the dist folder
    return gulp.src([`${dist}/**/*.js`, `${dist}/**/*.css`])
        .pipe($.cached('correct_lines_ending'))
        .pipe($.lineEndingCorrector())
        .pipe(gulp.dest(dist));
});


/**
 * Update textdomain
 */
gulp.task('update_text_domain', () => {
    // copy files to the dist folder
    return gulp.src(`${dist}/**/*.php`)
        .pipe($.cached('update_text_domain'))
        .pipe($.replace(new RegExp('(?!\')' + data.text_domain_define + '(?!\')', 'g'), '\'' + data.text_domain + '\''))
        .pipe(gulp.dest(dist));
});


/**
 * Remove unused constant
 */
gulp.task('remove_unused_constant', () => {
    var stringToReplace = '\ndefine( \'' + data.text_domain_define + '\', \'' + data.text_domain + '\' );\n';
    return gulp.src(`${dist}/*.php`)
        .pipe($.replace(stringToReplace, ''))
        .pipe($.replace(stringToReplace.replace(/\n/g, '\r'), ''))
        .pipe($.replace(stringToReplace.replace(/\n/g, '\r\n'), ''))
        .pipe(gulp.dest(dist));
});


/**
 * WP POT Translation File Generator.
 */
gulp.task('translate', () => {
    return gulp.src(`${dist}/**/*.php`)
        .pipe($.sort())
        .pipe($.wpPot( {
            domain        : data.text_domain,
            destFile      : `${data.name}.pot`,
            package       : data.title,
            lastTranslator: data.author,
            team          : data.author
        }))
        .pipe(gulp.dest(`${dist}/languages`));
});


/**
 * Main Build Task
 */
gulp.task('build', (cb) => {
    $.runSequence('clean', 'copy_to_dist', 'sass', 'sass-clean', 'js', 'correct_lines_ending', 'update_text_domain', 'remove_unused_constant', 'translate', cb);
});
gulp.task('watch_build', (cb) => {
    $.runSequence('copy_to_dist', 'sass', 'correct_lines_ending', 'update_text_domain', 'remove_unused_constant', 'translate', cb);
});


// watch for changes and run build task
gulp.task('watch', ['build'], () => {
    gulp.watch(`${src}/**/*`, ['watch_build']);
});

gulp.task('default', ['build']);