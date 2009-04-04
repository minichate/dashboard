<?php

// TODO:  Ask Chris or Adam to confirm that local cache code is working right header-wise

  // Code contains ideas from Joe Lencioni.
  // Changes made to this file by David Wolfe, Norex.

  // Currently, an image is readable if either 
  // (1)  there is a filename 'public' in the same directory has the source image, or
  // (2)  the user checks is logged in with admin privileges

/*
 * PARAMETERS PASSED THROUGH URL QUERY STRING
 *  image		(required) absolute path of local image starting with "/" (e.g. /images/toast.jpg)
 *  w or width	maximum width of final image in pixels (e.g. 700)
 *  h or height	maximum height of final image in pixels (e.g. 700)
 *  color		background hex color for filling transparent PNGs (e.g. 900 or 16a942) or for borders
 *  cropratio	ratio of width to height to crop final image (e.g. 1:1 or 3:2)
 *  nocache		does not read image from the cache
 *  quality		(0-100, default: 90) quality of output image
 *  type 		output image type jpg, png, or gif
 *  border 		add a border if this width --- color required
 *  fullw	    if specified, pad final image to this width with a border--- color required
 */

define('MEMORY_TO_ALLOCATE', '100M');
define('DEFAULT_QUALITY',	 90);
define('CURRENT_DIR',        dirname(__FILE__));
define('CACHE_DIR_NAME',     '/../cache/images/');
define('CACHE_DIR',          CURRENT_DIR . CACHE_DIR_NAME);
define('SITE_ROOT',      $_SERVER['DOCUMENT_ROOT']);
define('INFINITY', 999999);
$types = array ('image/gif' => 'gif',
				'gif' => 'gif',
				'image/x-png' => 'png',
				'image/png' => 'png',
				'png' => 'png',
				'image/jpeg' => 'jpeg',
				'image/jpg' => 'jpeg',
				'jpg' => 'jpeg',
	);
		
new ImageResizer(); // The consructor does all the work
////////////////////////////////////////////////////////////////
//  Remainder of file consists of class and function definitions
////////////////////////////////////////////////////////////////
function var_log($var, $prefix="") { // Used for debugging
	if ($prefix) $prefix .= ': ';
   	if ($var === null) $var = 'NULL';
   	elseif ($var === true) $var = 'true';
   	elseif ($var === false) $var = 'false';
	error_log ($prefix . print_r($var, true));
	return $var;
}

function request($var, $default = null) {
	return isset ($_REQUEST[$var]) ? $_REQUEST[$var] : $default;
}

function httpError($s, $code = 400) {
	header("HTTP/1.1 $code Bad Request");
	echo "Error: $s";
	exit();
}

function checkPerm ($file) {
	// Replace trailing filename with 'public'
	$publicFile = preg_replace('@/[^/]*$@', '/public', $file);
	$permitted = file_exists($publicFile) && file_exists($file);
	if (!$permitted) {
		require_once ('../include/Site.php');
		$u = @$_SESSION['authenticated_user'];
		$permitted = $u && $u->hasPerm('CMS', 'admin');
	}
	return $permitted;
}

function serveFile($filename, $mime, $data = null) {
	$lastModified = gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT';
	$etag = md5($filename . '//' . filesize($filename));
	doConditionalGet($etag, $lastModified); // Exit if local cache suffices
	$data = is_null($data) ? file_get_contents($filename) : $data;
	header("Content-type: $mime");
	header('Content-Length: ' . strlen($data));
	echo $data;
	exit();

}

function doConditionalGet($etag, $lastModified) { // Check if local (client side) cache has up-to-date version
	header("Last-Modified: $lastModified");
	header("ETag: \"{$etag}\"");
	$if_none_match = stripslashes((string) @$_SERVER['HTTP_IF_NONE_MATCH']);
	$if_modified_since = stripslashes((string) @$_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if (!$if_modified_since && !$if_none_match)	return;
	if ($if_none_match && $if_none_match != $etag && $if_none_match != '"' . $etag . '"') return;
	if ($if_modified_since && $if_modified_since != $lastModified) return;
	header('HTTP/1.1 304 Not Modified');
	exit();
}

class ImageResizer {
	private $doSharpen;
	public $new, $old;
	
	function __construct() {
		$this->old = new OldImage();
		$this->new = new NewImage($this->old);
		$this->checkNoChanges();
		$this->initCache();
		$this->loadImage();
		$this->computeWidthHeight();
		$this->outputImage(); // Sets upcanvas, does matting, copies image, etc.
	}
	function checkNoChanges() {
		foreach (array('image','w','width','h','height','color','cropratio','quality','type','border','fullw','fullh') as $arg)
			if (request($arg)) return;
		var_log ("SERVING ORIGINAL");
		serveFile($this->old->file(), $this->old->mime());
		die();
	}
	function loadImage() {
		$this->doSharpen = ($this->old->type == 'jpeg' && $this->new->type == 'jpeg');
		$creationFunction = "imagecreatefrom".$this->old->type;
		$this->old->image	= $creationFunction($this->old->file());
	}
	function computeWidthHeight () {
		if ($this->old->height <= $this->new->maxHeight
			&& $this->old->width <= $this->new->maxWidth) {
			$this->new->width = $this->old->width;
			$this->new->height = $this->old->height;
		} else {
			$diff = $this->old->height * $this->new->maxWidth - $this->old->width * $this->new->maxHeight;
			if ($diff == 0); // do nothing
			else if ($diff < 0) $this->new->height = $this->old->height * $this->new->maxWidth / $this->old->width;
			else $this->new->width = $this->old->width * $this->new->maxHeight / $this->old->height;
		}
		$this->new->computeFull();
		return;
	}

	function initCache() {
		if (isset($_REQUEST['nocache'])) return;
		require_once SITE_ROOT . '/core/PEAR/Cache/Lite.php';
		define ('CACHED_PAGE_INDEX', $_SERVER['REQUEST_URI']);
		$options = array(
			'cacheDir' => SITE_ROOT . '/cache/images/',
			'lifeTime' => 60*60*24*7 // 1 week
			);
		$this->cache = new Cache_Lite($options);
		if ($data = $this->cache->get(CACHED_PAGE_INDEX)) {
			serveFile($this->old->file(), $this->new->mime(), $data);
			die();
		}
	}
	
	function outputImage() {
		$this->new->setUpCanvas();
		$this->new->doColor();
		// TODO:  Resize only if new jpeg image is nearly the same size as old image jpeg.  Otherwise resample.
		// $resizeFunction = $this->doSharpen ? 'imagecopyresized' : 'imagecopyresampled'; // THIS IS JUST A GUESS
		$resizeFunction = 'imagecopyresampled';
		header('Content-type: ' . $this->new->mime());
		$resizeFunction($this->new->image, $this->old->image,
						$this->new->x, $this->new->y, $this->old->x, $this->old->y,
						$this->new->width, $this->new->height, $this->old->width, $this->old->height);
		$outputFunction = "image" . $this->new->type;
		ob_start();
		$outputFunction($this->new->image, null, $this->new->quality);
		$data = ob_get_contents();
		ob_end_clean();
		if (!isset($_REQUEST['nocache'])) $this->cache->save($data, CACHED_PAGE_INDEX);
		serveFile($this->old->file(), $this->new->mime(), $data);
		ImageDestroy($this->new->image);
		ImageDestroy($this->old->image);
	}
}

class Image {
	public $image, $width, $height, $type;
	function mime() {return "image/$this->type";}
}

class OldImage extends Image {
	private $file;
	function __construct() {
		$this->_getFile();
		$this->loadCrop();
	}
	private function _getFile() {
		global $types;
		$file = @$_REQUEST['image'];
		if (!$file) httpError('Image parameter required');
		if ($file[0] != "/") $file = "/$file";
		$this->file = SITE_ROOT . $file;
		if (!checkPerm($this->file)) httpError("Access to file denied");
		// Get the size and MIME type of the requested image
		$size = getimagesize($this->file);
		$this->type	= @$types[$size['mime']];
		if (!$this->type) httpError ("Type of file not a handled image type");
		$this->width = $size[0];
		$this->height = $size[1];
		return $this->file;
	}
	function loadCrop() {
		$this->crop = @$_REQUEST["cropratio"];
		$this->x = $this->y = 0; // Lower left corner
		if  (!$this->crop) return;
		$ratios = explode(":", $this->crop);
		if (2 != count($ratios)) httpError ("Malformed cropratio");
		$rw = $ratios[0];
		$rh = $ratios[1];
		$diff = $this->width * $rh - $this->height * $rw;
		if ($diff == 0) {
			return;
		} elseif ($diff < 0) { // Trim top and bottom
			$t = $this->height;
			$this->height = $this->width * $rh / $rw;
			$this->y = ($t - $this->height) / 2;
		}
		else { // Trim sides
			$t = $this->width;
			$this->width = $this->height * $rw / $rh;
			$this->x = ($t - $this->width) / 2;
		}
	}

	function file() {return $this->file;}
}

class NewImage extends Image {
	public $nogrow; // Please don't grow this image; default to true
	 
	function __construct($old) {
		// THE WORD "load" MEANS LOAD THE RELEVANT $_REQUEST PARAM AND UPDATE OBJECT ACCORDINGLY.
		$this->type = $old->type;
		$this->loadHeightWidth();
		$this->quality = request('quality', DEFAULT_QUALITY);
	}
	function loadHeightWidth() {
		$this->load("height");
		$this->load("width");
	}
	private function load($dim) { // $dim is "width" or "height"; loading from $_REQUEST, set to INFINITY if unspecified
		$Dim = ucfirst($dim);
		$maxDim = "max$Dim";
		$this->$dim = $this->$maxDim = (integer) @$_REQUEST[$dim];
		if (!$this->$maxDim) $this->$dim = $this->$maxDim = (integer) @$_REQUEST[$dim{0}];
		if (!$this->$maxDim) $this->$maxDim = INFINITY;
	}
	public function computeFull() {
		$w = request('fullw', $this->width);
		$h = request('fullh', $this->height);
		$b = request('border', 0);
		$this->fullWidth = max ($w, $this->width + 2*$b);
		$this->fullHeight = max ($h, $this->height + 2*$b);
		$this->x = ($this->fullWidth - $this->width) / 2;
		$this->y = ($this->fullHeight - $this->height) / 2;
	}
	public function doColor() {
		$color = preg_replace('/[^0-9a-fA-F]/', '', request('color',''));
		if ($color) {
			if ($color[0] == '#')
				$color = substr($color, 1);
			if (strlen($color) == 6)
				$background	= imagecolorallocate($this->image, hexdec($color[0].$color[1]), hexdec($color[2].$color[3]), hexdec($color[4].$color[5]));
			else if (strlen($color) == 3)
				$background	= imagecolorallocate($this->image, hexdec($color[0].$color[0]), hexdec($color[1].$color[1]), hexdec($color[2].$color[2]));
			else return;
			imagefill($this->image, 0, 0, $background);
		} else if ($this->type == '' || $this->type == 'png'){
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		}
	}
	public function setUpCanvas() {
		ini_set('memory_limit', MEMORY_TO_ALLOCATE);
		if ($this->type == 'png') // Convert 0-100 to 10-0
			$this->quality = round(10 - ($this->quality / 10));
		$this->image = imagecreatetruecolor($this->fullWidth, $this->fullHeight);
	}
}

die();
////////////////////////////////////////////////////////////////
// REST OF FILE IGNORED
////////////////////////////////////////////////////////////////


if ($doSharpen)
{
	// Sharpen the image based on two things:
	//	(1) the difference between the original size and the final size
	//	(2) the final size
	$sharpness	= findSharp($width, $tnWidth);
	
	$sharpenMatrix	= array(
		array(-1, -2, -1),
		array(-2, $sharpness + 12, -2),
		array(-1, -2, -1)
	);
	$divisor		= $sharpness;
	$offset			= 0;
	imageconvolution($dst, $sharpenMatrix, $divisor, $offset);
}

function findSharp($orig, $final) // function from Ryan Rud (http://adryrun.com)
{
	$final	= $final * (750.0 / $orig);
	$a		= 52;
	$b		= -0.27810650887573124;
	$c		= .00047337278106508946;
	
	$result = $a + $b * $final + $c * $final * $final;
	
	return max(round($result), 0);
}
