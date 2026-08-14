<?php

namespace App\Helpers\Customviewer;

use Illuminate\Support\Facades\Storage;

class LaravelFileViewer
{
    public function show(String $filename,String $filePath,String $file_url,$file_data=[])
    {
      //$type = \File::extension($filename);
        
      if(!Storage::exists($filePath)) {
        return view('noaccess.invalidfile');;
      } 
   
      $type = Storage::mimeType($filePath);
      

      $metadata = [
        'size' => Storage::size($filePath)
      ];
      $icon_class=$this->getIconClass($type);
      $filesizenyteformat=$this->formatBytes($metadata['size']);
      
      $icon_class=$this->getIconClass($type);
      $filesizenyteformat=$this->formatBytes($metadata['size']);
      $viewdata=compact('filename','file_url','type','file_data','metadata','icon_class','filesizenyteformat');

      switch ($type) {
          case 'image':
              return view('laravel-file-viewer::previewFileImage',$viewdata);
              break;
          case 'audio':
              return view('laravel-file-viewer::previewFileAudio',$viewdata);
              break;
          case 'video':
              return view('laravel-file-viewer::previewFileVideo',$viewdata);
              break;
          
          default:
          return view('laravel-file-viewer::previewFileOffice',$viewdata);
          // return view('laravel-file-viewer::previewFileGoogle',$viewdata);
              break;
      }
    }

       public function getIconClass($type){

        switch ($type) {
            case 'image':
                return 'fa-solid fa-file-image';
                break;
            
            case 'video':
                return 'fa-solid fa-file-video';
                break;
            
            case 'audio':
                return 'fa-solid fa-file-audio';
                break;

            case 'pdf':
              return 'fa-solid fa-file-pdf';
              break;

            case 'vnd.openxmlformats-officedocument.presentationml.presentation':
              return 'fa-solid fa-file-powerpoint';
              break;
          
            case 'vnd.openxmlformats-officedocument.wordprocessingml.document':
              return 'fa-solid fa-file-word';
              break;
          
            case 'msword':
              return 'fa-solid fa-file-word';
              break;
          
            case 'vnd.openxmlformats-officedocument.spreadsheetml.sheet':
              return 'fa-solid fa-file-excel';
              return 'fa-solid fa-file-spreadsheet';
              break;
            
            default:
            return 'fa-solid fa-file';
                break;
        }
       }
    function formatBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }
}
