<?

class FOnlineServer
{
    const MAGIC = 'FOSERV:';
    const ONLINE = 0;
    const ERR_NOT_CONFIGURED = -1;
    const ERR_FSOCKOPEN = -2;
    const ERR_FWRITE = -3;
    const ERR_UNPACK = -4;

    public $Name = NULL;
    public $Host = NULL;
    public $Port = 0;
    public $Status = self::ERR_NOT_CONFIGURED;
    public $Players = -1;
    public $Uptime = -1;

    public $LastPing = 0;

    private $LastAnswer;

    public function __construct()
    {
    }

    private function Clear()
    {
	$this->LastPing = $this->Players = $this->Uptime = 0;
	$this->Status = self::ERR_NOT_NONFIGURED;
    }

    public function Setup( $name, $host, $port = 4000 )
    {
	if( !isset($name) || !isset($host) || !isset($port) )
	    die( __METHOD__ . ' : isset' );

	if( empty($name) || empty($host) || empty($port) )
	    die( __METHOD__ . ' : empty' );

	if( !is_numeric($port) || $port <= 1024 || $port >= 65535 )
	    die( __METHOD__ . ' : $port' );

	$this->Name = $name;
	$this->Host = $host;
	$this->Port = (int)$port;
    }

    public function Load( $filename ) // WIP
    {
	if( !isset($filename) )
	    die( __METHOD__ . ' : isset' );

	if( empty($filename) )
	    die( __METHOD__ . ' : empty' );

	if( !is_file($filename) )
	    die( __METHOD__ . ' : is_file' );

	if( !file_exists($filename) )
	    die( __METHOD__ . ' : file_exists' );

	$file = fopen( $filename, 'rb' );
	if( $file )
	{
	    $magic = fread( $file, strlen(self::MAGIC) );
	    if( !isset($magic) || empty($magic) || $magic != self::MAGIC )
		die( __METHOD__ . ' : invalid magic' );

	    $this->Name = $name = '';

	    while( true ) // !
	    {
		if( feof( $file ))
		    break;

		if( !isset($this->Name) || empty($this->Name) )
		{
		    $byte = fread( $file, 1 );
		    if( $byte == '\xFF' || !ctype_alnum( $byte ))
		    {
			$this->Name = $name;
			continue;
		    }
		    $name .= $byte;
		}
		else
		{
		    $bytes = fread( $file, 6 );
		    if( strlen( $bytes ) == 6 )
		    {
			$data = array_merge( unpack( 'V1/s1/V1', $bytes ));
			if( count($data) == 3 )
			{
			    print_r($data);
			    if( $data['status'] >= 0 )
			    {
				$this->Status  = self::ONLINE;
				$this->Players = $data['status'];
				$this->Uptime  = $data['uptime'];
			    }
			    else
			    {
				$this->Status  = $data['status'];
				$this->Players = 0;
				$this->Uptime  = 0;
			    }
			}
			else
			    break;
		    }
		    else
			break;
		}
	    }
	}
    }

    function Save( $filename ) // WIP
    {
	$file = NULL;

	if( !file_exists( $filename ))
	{
	    $file = fopen( $filename, 'wb' );
	    if( $file )
	    {
		fwrite( $file, sprintf( "%s%s\xFF", self::MAGIC, $this->Name ));
		fclose( $file );
	    }
	}

	$file = fopen( $filename, 'ab' );
	if( $file )
	{
	    echo sprintf( "save %d %d\n", $this->LastPing, $this->Status == self::ONLINE ? $this->Players : $this->Status, $this->Uptime );
	    fwrite( $file, pack( 'VsV', $this->LastPing, $this->Status == self::ONLINE ? $this->Players : $this->Status, $this->Uptime ));
	    fclose( $file );
	}
    }

    function Ping( $timeoutConnect = 5, $timeoutReceive = 5 )
    {
	$this->LastPing = time();
	$this->LastAnswer = array();

	$socket = @fsockopen( $this->Host, $this->Port, $errno, $errstr, $timeoutConnect );
	if( $socket )
	{
	    $canRecv = true;

	    stream_set_timeout( $socket, $timeoutReceive );
	    $send = pack( 'L', 0xFFFFFFFF );
	    if( !fwrite( $socket, $send ))
	    {
		$this->LastAnswer = array( self::ERR_FWRITE );
		$canRecv = false; // goto?
	    }

	    if( $canRecv )
	    {
		$recv = fread( $socket, 16 );
		fclose( $socket );
		// $data = unpack( 'V*', $recv );
		$data = array_merge( unpack( 'V*', $recv ));
		if( count($data) == 4 )
		{
		    if( $data[0] < 0 || $data[1] < 0 )
			$this->LastAnswer = array( self::ERR_INVALID );
		    else
			$this->LastAnswer = $data;
		}
		else
		    $this->LastAnswer = array( self::ERR_UNPACK );
	    }
	}
	else
	    $this->LastAnswer = array( self::ERR_FSOCKOPEN );

	if( !is_array($this->LastAnswer) )
	    die( __METHOD__ . ' : LastAnswer is not an array' );
	elseif( count($this->LastAnswer) < 1 )
	    die( __METHOD__ . ' : LastAnswer too short' );

	if( $this->LastAnswer[0] >= 0 )
	{
	    $this->Status  = self::ONLINE;
	    $this->Players = $this->LastAnswer[0];
	    $this->Uptime  = $this->LastAnswer[1];
	}
	else
	{
	    $this->Status  = $this->LastAnswer[0];
	    $this->Players = -1;
	    $this->Uptime  = -1;
	}
    }

    public function PingSave( $filename )
    {
	$this->Ping();
	$this->Save( $filename );
    }
};

?>