<?php 
namespace App\Src\Facades;

use Illuminate\Support\Facades\Facade;

class Ftp extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { 
    	return 'ftp'; 
    }
}