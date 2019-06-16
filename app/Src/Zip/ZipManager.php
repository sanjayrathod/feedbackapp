<?php 
namespace App\Src\Zip;

use Config;
use ZipArchive;
use Illuminate\Support\Str;

class ZipManager {

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /** @var string */
    protected $pathToZip;

    /** @var string */
    protected $filename;

    /** @var int */
    protected $fileCount = 0;

    /**
     * Create a new FTP instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;

        $this->zipFile = new ZipArchive();

        $this->setPathToZip();

        $this->setDefaultFilename();
    }

    public function setDefaultFilename(): self
    {
        $this->filename = config::get('ftp.local_download_path').'.zip';

        return $this;
    }

    public function setPathToZip(string $pathToZip = '' ) {
        $this->pathToZip = (empty($pathToZip)) ? public_path() : $pathToZip;
    }

    public function count(): int
    {
        return $this->fileCount;
    }

    public function open()
    {
        $this->zipFile->open($this->filename, ZipArchive::CREATE);
    }

    public function close()
    {
        $this->zipFile->close();
    }

    /**
     * @param string|array $files
     * @param string $nameInZip
     *
     */
    public function add($files, string $pathToZip = null): self
    {
        // Temporary set the memory limit
        ini_set('memory_limit', '256M');

        $this->open();

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {

            if (file_exists($file)) {

                $this->zipFile->addFile($file, basename($file));
            }
            $this->fileCount++;
        }

        $this->close();

        return $this;
    }


}