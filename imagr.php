<?php

set_time_limit(60*60);
ini_set('memory_limit','1G');

require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();


$defaults =
	[
		'quality'     => getenv('quality'),
		'size'        => getenv('size'),
		'source'      => getenv('source'),
		'destination' => getenv('destination'),
	];


$climate = new League\CLImate\CLImate;

$climate->arguments->add([
    'size' => [
        'prefix'       => 's',
        'longPrefix'   => 'size',
        'description'  => 'The size of the longest dimension of the image (in px)',
        'defaultValue' => $defaults['size'],
    ],
    'quality' => [
        'prefix'      => 'q',
        'longPrefix'  => 'quality',
        'description' => 'The desired JPEG quality (in %)',
        'defaultValue' => $defaults['quality'],
    ],
    'source' => [
        'prefix'      => 's',
        'longPrefix'  => 'source',
        'description' => 'The folder with the images to be processed (absolute or relative)',
        'defaultValue' => $defaults['source'],
    ],
    'destination' => [
        'prefix'      => 'd',
        'longPrefix'  => 'destination',
        'description' => 'The folder where the processed images are to be stored (absolute or relative)',
        'defaultValue' => $defaults['destination'],
    ],
    'help' => [
        'longPrefix'  => 'help',
        'description' => 'Prints a usage statement',
        'noValue'     => true,
    ],
]);

$climate->arguments->parse();

if ($climate->arguments->defined('help')) {
	$climate->description('imagr');
	$climate->usage();
	exit;
}


$climate->flank('imagr');

if (!$climate->arguments->defined('size')) {
	$input = $climate->input('Maximum image dimension (in px) [default='.$defaults['size'].']: ');
	$input->defaultTo($defaults['size']);
	$size  = $input->prompt();
} else {
	$size = $climate->arguments->get('size');
}

if (!$climate->arguments->defined('quality')) {
	$input   = $climate->input('JPEG image quality (in %) [default='.$defaults['quality'].']: ');
	$input->defaultTo($defaults['quality']);
	$quality = $input->prompt();
} else {
	$quality = $climate->arguments->get('quality');
}

if (!$climate->arguments->defined('source')) {
	$input   = $climate->input('Source folder (absolute or relative) [default='.$defaults['source'].']: ');
	$input->defaultTo($defaults['source']);
	$source = $input->prompt();
} else {
	$source = $climate->arguments->get('source');
}

if( !file_exists($source)) {
	$climate->error('Source folder does not exist.');
	exit;
}

if (!$climate->arguments->defined('destination')) {
	$input   = $climate->input('Destination folder (absolute or relative) [default='.$defaults['destination'].']: ');
	$input->defaultTo($defaults['destination']);
	$destination = $input->prompt();
} else {
	$destination = $climate->arguments->get('destination');
}

if( !file_exists($destination)) {
	$climate->error('Destination folder does not exist.');
	exit;
}

$files = array_merge(glob($source.'/*.jpg'), glob($source.'/*.JPG'), glob($source.'/*.jpeg'));

$progress = $climate->progress()->total(count($files));

$i = 0;
foreach($files as $f){
	$options = ['resizeUp' => true, 'jpegQuality' => $quality];
	$thumb   = new PHPThumb\GD($f, $options);
	$paths   = pathinfo($f);

	$thumb->resize($size, $size);
	$thumb->save($destination.'/'.$paths['filename'].'_s'.$size.'-q'.$quality.'.'.$paths['extension'], 'jpg');

	unset($thumb);

	$i++;
	$progress->current($i);
}

$climate->br()->green('Successfully processed '.count($files).' pictures.')->out('You can find the resized images in the destination folder. The originals weren\'t touched.');