Classes: use CamelCase and have the filename be ClassName.class.php
    ex: TranscriptParser, JSgenerator...oops.

methods/functions: all lowercase, use underscores to separate words
    ex: get_html_base_path(), add_global_code()

variable naming: use "_" to separate words. No camelcase!

filenames: use dashes (-) to separate words, no capitalization except for classes
ex: admin-top.php, setup-tables.php

includes: place in includes/, prefix function collections with "functions-"
ex: functions-episode-specific.php

constants: all uppercase, use "_" to separate words,

path constants: Postfix with _PATH, if webserver path (like "/admin/js/"), then
		postfix with "_HTML_PATH". Always end them with a "/".

Javascript callbacks: place in CALLBACK_PATH, prefix with "cb-"



