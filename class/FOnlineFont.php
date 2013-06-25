<?php

class FOnlineFontLetter
{
    public $PositionX = 0;
    public $PositionY = 0;
    public $Width = 0;
    public $Height = 0;
    public $OffsetX = 0;
    public $OffsetY = 0;
    public $XAdvance = 0;
    public $YAdvance = 0;

    protected $Image = NULL;

    public function __construct( $filename )
    {
	if( !isset($filename) )
	    die( __METHOD__ . ' : isset' );

	if( empty($filename) )
	    die( __METHOD__ . ' : empty' );

	if( !is_file($filename) )
	    die( __METHOD__ . ' : is_file' );

	if( !file_exists($filename) )
	    die( __METHOD__ . ' : file_exists' );

	$this->Image = realpath($filename);
    }
};

class FOnlineFont
{
    public $LineHeight = 0;
    public $YAdvance = 0;

    public $Image = NULL;
    public $Letters = array();
    public $Version = 0;

    public function __construct( $filename )
    {
	if( !isset($filename) )
	    die( __METHOD__ . ' : isset' );

	if( empty($filename) )
	    die( __METHOD__ . ' : empty' );

	if( !is_file($filename) )
	    die( __METHOD__ . ' : is_file' );

	if( !file_exists($filename) )
	    die( __METHOD__ . ' : file_exists' );

	$lines = explode( "\n", file_get_contents( $filename ));

	if( !isset($lines) )
	    die( __METHOD__ . ' : lines:isset' );

	if( empty($lines) )
	    die( __METHOD__ . ' : lines:empty' );

	if( !count($lines) )
	    die( __METHOD__ . ' : lines:count=0' );

	$lastLetter = NULL;
	foreach( $lines as $num => $line )
	{
	    if( preg_match( '/^[ \t]*#/', $line ))
		continue;
	    if( preg_match( '/^[ \t]*;/', $line ))
		continue;

	    $line = trim( $line );
	    $line = str_replace( "\r", '', $line );
	    $line = str_replace( "\n", '', $line );

	    if( !isset($line) || empty($line) )
		continue;

	// version
	    if( !$this->Version )
	    {
		if( preg_match( '/^Version[\ \t]+([0-9]+)$/', $line, $match ))
		{
		    $version = (int)$match[1];
		    if( $version != 2 /* $version < 2 || $version > 3 */ )
			die( __METHOD__ . " : unknown font version <$version>" );
		    $this->Version = $version;
		}
		else
		    // "Version" must be first setting in file
		    die( __METHOD__ . ' : font version not set' );
	    } // !$this->Version
	    else if( $this->Version == 2 )
	    {
	    // global settings
		if( preg_match( '/^Image[\ \t]+(.*)$/', $line, $match ))
		    $this->Image = dirname( realpath( $filename )) . '/' . $match[1];
		else if( preg_match( '/^LineHeight[\ \t]+([0-9]+)$/', $line, $match ))
		    $this->LineHeight = (int)$match[1];
		else if( preg_match( '/^YAdvance[\ \t]+([0-9]+)$/', $line, $match ))
		    $this->YAdvance = (int)$match[1];

	    // letter settings
		else if( preg_match( '/^Letter[\ \t]+\'(.*)\'/', $line, $match ))
		{
		    $lastLetter = $match[1];
		    $this->Letters[$lastLetter] = new FOnlineFontLetter( $this->Image );
		    $this->Letters[$lastLetter]->YAdvance = $this->YAdvance;
		}
		elseif( preg_match( '/^PositionX[\ \t]+([0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->PositionX = (int)$match[1];
		elseif( preg_match( '/^PositionY[\ \t]+([0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->PositionY = (int)$match[1];
		elseif( preg_match( '/^Width[\ \t]+([0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->Width = (int)$match[1];
		elseif( preg_match( '/^Height[\ \t]+([0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->Height = (int)$match[1];
		elseif( preg_match( '/^XAdvance[\ \t]+([\-0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->XAdvance = (int)$match[1];
		elseif( preg_match( '/^OffsetX[\ \t]+([\-0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->OffsetX = (int)$match[1];
		elseif( preg_match( '/^OffsetY[\ \t]+([\-0-9]+)/', $line, $match ))
		    $this->Letters[$lastLetter]->OffsetY = (int)$match[1];
		else
		    die( __METHOD__ . " : unknown line<$line> line $num" );
	    } // $this->Version == 2
	}
    }
};

?>
