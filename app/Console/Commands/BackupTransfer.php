<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use Config;
use FTP;
use ZIP;
use File;

class BackupTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'take:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a backup from source server and upload to destination server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Script start at '.date('Y-m-d H:i:s')) . PHP_EOL;

        $this->info('Checking source FTP login...') . PHP_EOL;
        if(FTP::connection('source')) {
            $this->info('Source FTP login success');

            $sourceFTPDir = FTP::connection('source')->getDirListing();

            if (empty($sourceFTPDir)) {
                $this->error('Nothing found on FTP!');
            } else {
                
                $this->info('Start downloading...');

                $localDownloadPath = config::get('ftp.local_download_path');

                if (!file_exists($localDownloadPath)) {
                    mkdir($localDownloadPath, 0777);
                }

                if(FTP::connection('source')->downloadFile('readme.txt', $localDownloadPath . DIRECTORY_SEPARATOR . 'readme.txt', FTP_BINARY)) {

                    // close Source FTP connection
                    FTP::disconnect('source');

                    $this->info('Start creating zip file');

                    $filesInFolder = File::allFiles(config::get('ftp.local_download_path'));     
                    foreach($filesInFolder as $path) { 
                        $file = pathinfo($path);
                        
                        // Create zip file 
                        Zip::add(
                            $file['dirname'].DIRECTORY_SEPARATOR.$file['filename']. '.'.$file['extension'], 
                            public_path() . DIRECTORY_SEPARATOR
                        );
                    }

                    $this->info('Zip file created successfully');
                    
                    $this->info('Checking destination FTP login...');
                    // Connect to destination FTP client
                    $destinationFTPDir = FTP::connection('destination')->getDirListing();

                    if (empty($destinationFTPDir)) {
                        $this->error('Nothing found on FTP!');
                    } else {
                        // Start uploading the source zip
                        $uploadStatus = FTP::connection('destination')->uploadFile(
                            $localDownloadPath . '.zip',
                            '.done/' . basename($localDownloadPath). '.zip',
                            FTP_BINARY
                        );

                        var_dump($uploadStatus);

                        $this->info('Zip file uploaded successfully');

                        FTP::disconnect('destination');

                        $this->info('Script end at '.date('Y-m-d H:i:s')) . PHP_EOL;
                    }

                } else {
                    $this->error('Download failed!');
                }

            }

        } else {
            $this->error('Invalid login cradentials.');
        }
    }
}
