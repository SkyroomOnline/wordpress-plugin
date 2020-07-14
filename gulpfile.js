/**
 * Gulpfile
 *
 * Skyroom wordpress plugin automation tasks.
 */
const gulp = require('gulp');
const zip = require('gulp-zip');
const rename = require('gulp-rename');

/**
 * Wrap plugin files into a zip.
 */
gulp.task('wrap', () => {
    return gulp.src(['**', '!dist/**', '!node_modules/**', '!composer.*', '!packages.json', '!yarn.lock', '!gulpfile.js'])
        .pipe(rename((path) => path.dirname = 'skyroom/' + path.dirname))
        .pipe(zip('skyroom.zip'))
        .pipe(gulp.dest('dist'));
});

/**
 * Default task, only runs wrap task.
 */
gulp.task('default', gulp.series('wrap'));
