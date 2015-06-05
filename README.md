# minifier
Automatic minify for CSS and JS files controlled by config file.

Format of the config file *files-minifier.lst*:
/path/to/the/css_or_js [optional unix_timestamp]

Script recognize file type by extension and adds .min.[original extension] postfix to an output file.
