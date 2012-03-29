<?PHP // $Id: loader.php,v 1.3 2005/08/06 15:07:34 jamiesensei Exp $
      // This function fetches user pictures from the data directory
      // Syntax:   pix.php/userid/f1.jpg or pix.php/userid/f2.jpg
      //     OR:   ?file=userid/f1.jpg or ?file=userid/f2.jpg

    $nomoodlecookie = true;     // Because it interferes with caching

    require_once('../../config.php');
    require_once($CFG->libdir.'/filelib.php');

    $relativepath = get_file_argument('loader.php');

    $args = explode('/', trim($relativepath, '/'));

    if (count($args) == 4) {
        $moviename  = basename($args[0]);
        $width   = intval($args[1]);
        $height   = intval($args[2]);
        $framerate   = intval($args[3]);
        $fullmoviename="$moviename{$width}x{$height}x{$framerate}.swf";
        $pathname = "$CFG->dataroot/flash_moviecache/$fullmoviename";
    }

    if (file_exists($pathname) and !is_dir($pathname)) {
        send_file($pathname, $fullmoviename, 15552000);//expire after 180 days
    } else {
        header('HTTP/1.0 404 not found');
        error(get_string('filenotfound', 'error')); //this is not displayed on IIS??
    }
?>
