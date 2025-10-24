<?php

/**
 * Configuration for folders to have access to .web
 */
foreach (glob(__DIR__.'/*/*/*/web.php') as $filename) {
    require $filename;
}
