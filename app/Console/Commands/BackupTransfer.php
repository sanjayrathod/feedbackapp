<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use Config;
use FTP;
use ZIP;
use File;
//use ZipArchive;

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
        //$this->zip = new ZipArchive();
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
        

//echo "<pre>"; print_r($this->zipFile); exit;
// $this->createZip(
//     array(
//         public_path() . DIRECTORY_SEPARATOR . 'test.txt',
//         public_path() . DIRECTORY_SEPARATOR . 'index.php'
//     ),
//     public_path() . DIRECTORY_SEPARATOR
// );
/*echo "<pre>"; print_r(
    Zip::add(
        array(
            public_path() . DIRECTORY_SEPARATOR . 'test.txt',
            public_path() . DIRECTORY_SEPARATOR . 'index.php'
        ),
        public_path()
    )); exit;*/
        // $this->info('Starting backup script...');

        // $this->info('Check and Connect to source FTP!');

        //$sourceFtpConnection = FTP::connection()->getDirListing();
        //echo "<pre>"; print_r($sourceFtpConnection); exit;
        // $sourceFtpConnection = FTP::connection('destination')->downloadFile(
        //     './done/Functional_Testing_Network_Components.zip',
        //     public_path() . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR ,
        //     FTP_BINARY
        // );
//lk,j njk,jecho base_path() . DIRECTORY_SEPARATOR . '2019-06-16-11-40-41.zip'; exit;
        // $destinationFTPConnection = FTP::connection('destination')->uploadFile(
        //     base_path() . DIRECTORY_SEPARATOR . '2019-06-16-11-40-41.zip',
        //     '.done/2019-06-16-11-40-41.zip',
        //     FTP_BINARY
        // );
//         $destinationFTPConnection = FTP::connection('destination')->delete('.done/sourcetest.txt');
//echo "<pre>"; var_dump($destinationFTPConnection); exit;        
        /*$destinationFTPConnection = FTP::connection('destination')->downloadDirectory(
            '.done',
            public_path() . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR
        );*/

        /*$destinationFTPConnection = FTP::connection('destination')->getDirListingDetailed();
echo "<pre>"; print_r($destinationFTPConnection); exit;*/

    }


    /**
     * createZip description
     * @param  array  $files [description]
     * @return [type]        [description]
     */
    // public function createZip(array $files, $zipFilePath = '', $zipFileName = 'backup_sanjay.zip') {

    //     $this->zip->open($zipFileName, ZipArchive::CREATE);

    //     foreach ($files as $key => $file) {
    //         $this->zip->addFile($file, basename($file));
    //     }

    //     $this->zip->close();
    // }
}
