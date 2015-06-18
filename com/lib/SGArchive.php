<?php
require_once(SG_LIB_PATH.'BigInteger.php');

interface SGArchiveDelegate
{
	public function getCorrectCdrFilename($filename);
	public function didExtractFile($filePath);
    public function didCountFilesInsideArchive($count);
    public function didFindExtractError($error);
}

class SGArchive
{
	const VERSION = 1;
	const CHUNK_SIZE = 1048576; //1mb
	private $filePath = '';
	private $mode = '';
	private $fileHandle = null;
	private $cdr = array();
	private $fileOffset = null;
	private $delegate;
	
	public function __construct($filePath, $mode)
	{
		$this->filePath = $filePath;
		$this->mode = $mode;
		$this->fileHandle = @fopen($filePath, $mode.'b');
		$this->clear();
	}
	
	public function setDelegate(SGArchiveDelegate $delegate)
	{
		$this->delegate = $delegate;
	}
	
	public function addFileFromPath($filename, $path)
	{
		$headerSize = $this->addFileHeader();
		
		$zlen = new Math_BigInteger(0);
		$len = new Math_BigInteger(0);
		
		$fp = fopen($path, 'rb');
		$filter = stream_filter_append($fp, 'zlib.deflate', STREAM_FILTER_READ);
		
		//read file in small chunks
		while (!feof($fp))
		{
			$data = fread($fp, self::CHUNK_SIZE);
			$zlen = $zlen->add(new Math_BigInteger(strlen($data)));
			$this->write($data);
		}
		
		stream_filter_remove($filter);
		fclose($fp);
		
		$this->addFileToCdr($filename, $zlen, $len, $headerSize);
	}

	public function addFile($filename, $data)
	{
		$headerSize = $this->addFileHeader();

		if ($data)
		{
			$data = gzdeflate($data);
			$this->write($data);
		}
		
		$zlen = new Math_BigInteger(strlen($data));
		$len = new Math_BigInteger(0);

		$this->addFileToCdr($filename, $zlen, $len, $headerSize);
	}

	private function addFileHeader()
	{
		//save extra
		$extra = '';
		$extraLengthInBytes = 4;
		$this->write($this->packToLittleEndian(strlen($extra), $extraLengthInBytes).$extra);
		
		return $extraLengthInBytes+strlen($extra);
	}

	private function addFileToCdr($filename, $zlen, $len, $headerSize)
	{
		//store cdr data for later use
		$this->addToCdr($filename, $zlen, $len);
		
		$this->fileOffset = $this->fileOffset->add(new Math_BigInteger($headerSize));
		$this->fileOffset = $this->fileOffset->add($zlen);
	}
	
	public function finalize()
	{
		$this->addFooter();
		
		fclose($this->fileHandle);
		
		$this->clear();
	}
	
	private function addFooter()
	{
		$footer = '';
		
		//save version
		$footer .= $this->packToLittleEndian(self::VERSION, 1);

		//save extra (not used in this version)
		$extra = '';
		$footer .= $this->packToLittleEndian(strlen($extra), 4).$extra;

		//save cdr size
		$footer .= $this->packToLittleEndian(count($this->cdr), 4);

		//save cdr
		$cdr = implode('', $this->cdr);
		$footer .= $cdr;

		//save number of bytes from here to the start of cdr
		$len = strlen($cdr)+strlen($extra)+13;
		$footer .= $this->packToLittleEndian($len, 4);
		
		$this->write($footer);
	}
	
	private function clear()
	{
		$this->cdr = array();
		$this->fileOffset = new Math_BigInteger(0);
	}
	
	private function addToCdr($filename, $compressedLength, $uncompressedLength)
	{
		$rec = $this->packToLittleEndian(0, 4); //crc (not used in this version)
		$rec .= $this->packToLittleEndian(strlen($filename), 2);
		$rec .= $filename;
		$rec .= $this->packToLittleEndian($this->fileOffset);
		$rec .= $this->packToLittleEndian($compressedLength);
		$rec .= $this->packToLittleEndian($uncompressedLength); //uncompressed size (not used in this version)
		
		$this->cdr[] = $rec;
	}
	
	private function write($data)
	{
		fwrite($this->fileHandle, $data);
		fflush($this->fileHandle);
	}
	
	private function read($length)
	{
		return fread($this->fileHandle, $length);
	}
	
	private function packToLittleEndian($value, $size = 4)
	{
		if (is_int($value))
		{
			$size *= 2; //2 characters for each byte
			$value = str_pad(dechex($value), $size, '0', STR_PAD_LEFT);
			return strrev(pack('H'.$size, $value));
		}
		
		$hex = str_pad($value->toHex(), 16, '0', STR_PAD_LEFT);

		$high = substr($hex, 0, 8);
		$low  = substr($hex, 8, 8);

		$high = strrev(pack('H8', $high));
		$low = strrev(pack('H8', $low));

		return $low.$high;
	}
	
	public function extractTo($destinationPath)
	{
		//read offset
		fseek($this->fileHandle, -4, SEEK_END);
		$offset = hexdec($this->unpackLittleEndian($this->read(4), 4));

		//read version
		fseek($this->fileHandle, -$offset, SEEK_END);
		$version = hexdec($this->unpackLittleEndian($this->read(1), 1));
		
		if ($version != self::VERSION)
		{
			throw new SGExceptionBadRequest('Invalid SGArchive file');
		}

		//read extra size (not used in this version)
		$this->read(4);

		//read cdr size
		$cdrSize = hexdec($this->unpackLittleEndian($this->read(4), 4));
		
		$this->delegate->didCountFilesInsideArchive($cdrSize);
		
		$this->extractCdr($cdrSize, $destinationPath);
		$this->extractFiles($destinationPath);
	}
	
	private function extractCdr($cdrSize, $destinationPath)
	{
		while ($cdrSize)
		{
			//read crc (not used in this version)
			$this->read(4);
			
			//read filename
			$filenameLen = hexdec($this->unpackLittleEndian($this->read(2), 2));
			$filename = $this->read($filenameLen);
			$filename = $this->delegate->getCorrectCdrFilename($filename);
			
			//read file offset (not used in this version)
			$this->read(8);
			
			//read compressed length
			$zlen = $this->unpackLittleEndian($this->read(8), 8);
			$zlen = new Math_BigInteger($zlen, 16);
			
			//read uncompressed length (not used in this version)
			$this->read(8);
			
			$cdrSize--;
			
			$path = $destinationPath.$filename;
			$path = str_replace('\\', '/', $path);

			if ($path[strlen($path)-1] != '/') //it's not an empty directory
			{
				$path = dirname($path);
			}

			if (!$this->createPath($path))
			{
				$this->delegate->didFindExtractError('Could not create directory: '.dirname($path));
				continue;
			}
			
			$this->cdr[] = array($filename, $zlen);
		}
	}
	
	private function extractFiles($destinationPath)
	{
		$zero = new Math_BigInteger(0);
		$blockSize = new Math_BigInteger(self::CHUNK_SIZE);
		
		fseek($this->fileHandle, 0, SEEK_SET);
		
		foreach ($this->cdr as $row)
		{
			//read extra (not used in this version)
			$this->read(4);

			$path = $destinationPath.$row[0];
			if (!is_writable(dirname($path)))
			{
				$this->delegate->didFindExtractError('Destination path is not writable: '.dirname($path));
			}
			
			$fp = @fopen($path, 'wb');
			if (is_resource($fp))
			{
				$filter = stream_filter_append($fp, 'zlib.inflate', STREAM_FILTER_WRITE);
			}
			
			$zlen = $row[1];
			
			while ($zlen->compare($zero)>0)
			{
				$readlen = $zlen->compare($blockSize)>=0?self::CHUNK_SIZE:(int)$zlen->toString();
				$data = $this->read($readlen);
				if (is_resource($fp)) {
					fwrite($fp, $data);
					fflush($fp);
				}

				$zlen = $zlen->subtract($blockSize);
			}
			
			if (is_resource($fp))
			{
				stream_filter_remove($filter);
				fclose($fp);
			}
			
			$this->delegate->didExtractFile($path);
		}
	}
	
	private function unpackLittleEndian($data, $size)
	{
		$size *= 2; //2 characters for each byte
		
		$data = unpack('H'.$size, strrev($data));
		return $data[1];
	}
	
	private function createPath($path)
	{
		if (is_dir($path)) return true;
		$prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
		$return = $this->createPath($prev_path);
		if ($return && is_writable($prev_path))
		{
			if (!@mkdir($path)) return false;

			@chmod($path, 0777);
			return true;
		}
		
		return false;
	}
}