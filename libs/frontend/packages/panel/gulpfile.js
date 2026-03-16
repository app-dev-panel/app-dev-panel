const gulp = require('gulp');
var concat = require('gulp-concat');

gulp.task(
    'default',
    gulp.series(
        gulp.parallel(
            () => gulp.src(['build/static/js/*.js']).pipe(concat('bundle.js')).pipe(gulp.dest('build')),
            () => gulp.src(['build/static/js/*.map']).pipe(gulp.dest('build')),
            () => gulp.src(['build/static/css/*.css']).pipe(concat('bundle.css')).pipe(gulp.dest('build')),
            () => gulp.src(['build/static/css/*.map']).pipe(gulp.dest('build')),
        ),
    ),
);
