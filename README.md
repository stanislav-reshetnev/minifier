It is console utility for automatic minify CSS and JS files controlled by list file. You can add it to *crontab* like that:

`*/5 * * * *     /usr/bin/php /usr/local/sbin/minifier/mini.php >> /var/log/minifier.log`

Because it watchs file modification time and compares with own file timestamp, it is useful for regular executions. It will minimize recently updated files automatically.

## List file

The list file is *files-minifier.lst*.

Each line of this file contains paths of files to convert:

`/path/to/the/css_or_js [optional unix_timestamp]`

## Processing

For each path placed in *files-minifier.lst* the script compares file's modification date with given timestamp. If there is no timestamp it will allways process the file. After processing the script writes current timestamps for processed files.

The script recognizes file type by extension and adds .min.[original extension] postfix to output file. Output file will have the same permissions and owner as original file.

## External libraries

The script uses:
* [Google Closure Compiler](https://developers.google.com/closure/compiler/docs/api-ref) for JavaScripts;
* CssMin library cloned into this repository from [GoogleCode](https://code.google.com/p/cssmin/) for CSS.
