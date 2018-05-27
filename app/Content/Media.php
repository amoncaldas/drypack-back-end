<?php

namespace App\Content;

use \Imagick;
use App\BaseModel;
use App\Util\DryPack;
use App\Content\Content;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class Media extends BaseModel
{
    /**
     * Constats to define values used in the class
     */
    public const STORAGE_POLICY_FILESYSTEM = "filesystem";
    public const STORAGE_POLICY_INDB = "indb";
    public const DIMENSION_TYPE_RESPONSIVE = "responsive";
    public const DIMENSION_TYPE_SIZED = "sized";
    public const THUMB_FORMAT = "jpg";
    public const VIDEO_TYPE = "video";
    public const IMAGE_TYPE = "image";
    public const AUDIO_TYPE = "audio";
    public const HTTP_PROTOCOL_START = "http:";
    public const THUMB_SUFFIX = "_thumb_";
    public const THUMB_PATH = "thumb_path";
    public const THUMB_TEMP_PATH = "thumb_path";
    public const UPLOAD_PATH = "upload_path";
    public const PIXEL_UNIT = "px";
    public const BASE64_IMAGE_PREFIX = "data:image/jpg;base64,";


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "medias";

     /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['file_name','unique_name','type',
        'status','author_name','author_id', 'mimetype','ext',
        'length', 'content', 'thumb', 'url', 'owner_id', 'storage_policy'
    ];

    /**
     * Transient imagick object representing the full image
     *
     * @var Imagick
     */
    protected $imagickOriginaImage = null;

    /**
     * Get the the imgick representation of the original image
     *
     * @param boolean $fresh - if we should get a fresh represenation, discarting the in memory one
     * @return Imagick
     */
    public function getOriginaImageImagick($fresh = false) {
        if ($this->imagickOriginaImage === null || $fresh === true) {
            $base64 = $this->getBase64ImageContent();
            $imageBlob = base64_decode($base64);
            $imagick = new Imagick();
            $imagick->readImageBlob($imageBlob);
            $this->imagickOriginaImage = $imagick;
        }
        return $this->imagickOriginaImage;
    }


    /**
    * Return the relationship with the parent project
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    /**
     * Resolve media file location
     *
     * @return void
     */
    public function resolveFileLocation() {
        $savePath = $this->getSavePath(self::UPLOAD_PATH);
        $filePath = $savePath."/".$this->unique_name;
        return $filePath;
    }

    /**
     * Prepare save, create thumb (if auto_thumb is true) and save in db
     *
     * @param array $options
     * @return void
     */
    public function save(array $options = []) {
        $this->prepareSave();
        if ($this->type !== self::AUDIO_TYPE && Config::get('media-uploader.auto_thumb') === true) {
            $this->createAutoThumb();
        }
        parent::save($options);
    }

    /**
     * Get a thumb according the options specified. If the thumb does not exist, create a fresh one on disk
     *
     * @param array $options
     * @return string $thumbBase64
     */
    public function getThumb($options) {

        // try to get the auto thumb if the options match the uato thumb config
        $thumbBase64 = $this->getAutotThumb($options);

        // if not, get a custom thumb already generated or generate a new one
        if ($thumbBase64 ===false) {
            $thumbUri = $this->resolveThumbFileLocation($options["width"], $options["height"]);
            if (File::exists($thumbUri)) {
                $blobContent = file_get_contents($thumbUri);
                $thumbBase64 = base64_encode($blobContent);
            } else {
                $imagickThumb = $this->createThumb($autoWidth, $autoHeight);
                $thumbSaveFileName = $this->getThumbFileName($thumbWidth, $thumbHeight);
                $thumbPath = $thumbPath = $this->getSavePath(self::THUMB_PATH);
                $imagickThumb->writeimage("$thumbPath/$thumbSaveFileName");
                $thumbBase64 = base64_encode($imagickThumb->getImageBlob());
            }
            if (isset($options["include_base64_prefix"])) {
                return self::BASE64_IMAGE_PREFIX.$thumbBase64;
            }
        }
        return $thumbBase64;
    }

    /**
     * Generate a unique file name
     *
     * @param string $fileName
     * @param string $extension
     * @return string $uniqueFileName
     */
    public static function generateUniqueFileName($fileName, $extension) {
        $timeStamp = (new \DateTime())->getTimestamp();
        $originalFileName = str_replace(".".$extension, "", $fileName);
        $uniqueFileName = $originalFileName."_".$timeStamp. '.' . $extension;
        return $uniqueFileName;
    }

    /**
     * Get the base64 representation of the full or preview image of the media
     *
     * @param boolean $includePrefix
     * @return string|null
     */
    public function getBase64ImageContent($includePrefix = false) {
        if($this->type === self::IMAGE_TYPE) {
            if($this->storage_policy === self::STORAGE_POLICY_INDB) {
                return $this->content;
            } else {
                $imageUri = $this->resolveFileLocation();
                $blobContent = file_get_contents($imageUri);
                $base64 = base64_encode($blobContent);
                return $includePrefix? self::BASE64_IMAGE_PREFIX.$base64 : $base64;
            }
        } else if ($this->type === self::VIDEO_TYPE) {
            if($this->preview_image !== null) {
                if(DryPack::startsWith($this->preview_image, self::HTTP_PROTOCOL_START)) {
                    $blobContent = file_get_contents($this->preview_image);
                    $base64 = base64_encode($blobContent);
                    return $includePrefix? self::BASE64_IMAGE_PREFIX.$base64 : $base64;
                } else {
                    return $includePrefix? self::BASE64_IMAGE_PREFIX.$this->preview_image : $this->preview_image;
                }
            }
        }
        return null;
    }

    /**
     * Resolve the thumb file location
     *
     * @param integer $width
     * @param integer $height
     * @return string $thumbFilePath
     */
    public function resolveThumbFileLocation($width, $height) {
        $savePath = $this->getSavePath(self::THUMB_PATH);
        $thumbFilePath = $savePath."/".$this->getThumbFileName($width, $height);
        return $thumbFilePath;
    }

    /**
     * Save an uploaded file to the file system
     *
     * @param UploadedFile $file
     * @return string|false $savedFileName
     */
    public function saveInFileSystem(UploadedFile $file) {
        $extension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();

        $saveFileName = Media::generateUniqueFileName($fileName, $extension);
        $savePath = $this->getSavePath(self::UPLOAD_PATH);

        $moved = $file->move($savePath, $saveFileName);

        if($moved != null && $moved !== false) {
            return $saveFileName;
        }
        return false;
    }

    /**
     * Get the base64 representation of the temp uploaded file
     *
     * @param [type] $file
     * @return void
     */
    public function getTempUploadBase64($file) {
        $path = $file->getPathName();
        $blobContent = file_get_contents($path);
        return base64_encode($blobContent);
    }

     /**
     * Create the auto thumb
     *
     * @return void
     */
    protected function createAutoThumb() {
        if ($this->type !== self::AUDIO_TYPE) {
            $thumbWidth = Config::get('media-uploader.auto_thumb_width');
            $thumbHeight = Config::get('media-uploader.auto_thumb_height');
            $thumbProportional = Config::get('media-uploader.auto_thumb_proportional');

            $imagickThumb = $this->createThumb($thumbWidth, $thumbHeight, $thumbProportional);
            $this->thumb = base64_encode($imagickThumb->getImageBlob());
        }
    }

    /**
     * Create a Imagick thumb
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $proportional
     * @return Imagick
     */
    protected function createThumb($width, $height, $proportional = false) {
        if ($this->type !== self::AUDIO_TYPE) {
            $imagick  = $this->getOriginaImageImagick();
            if ($proportional === true) {
                $imagick->thumbnailImage($width, $height,true, true);
            } else {
                $imagick->cropThumbnailImage($width, $height);
            }

            $imagick->setFormat(self::THUMB_FORMAT);
            return $imagick;
        }
    }

    /**
     * Get the auto gennerated thumb, if the options match with the auto thumb config
     *
     * @param array $options
     * @return string $thumbBase64
     */
    protected function getAutotThumb($options) {
        $autoWidth = Config::get('media-uploader.auto_thumb_width');
        $autoHeight = Config::get('media-uploader.auto_thumb_height');
        $width = isset($options["width"]) ? $options["width"] : false;
        $height = isset($options["height"])? $options["height"] : false;

        /**
         * If the width and height were not informed, or they are the same of the auto thumb, get the auto thumb
         */
        if(($width === false && $height === false) || ($width === $autoWidth && $height === $autoHeight)) {
            $thumbBase64 = $this->thumb;
            if ($thumbBase64 === null) {
                $proportional = isset($options["proportional"]) ? $options["proportional"] : false;
                $imagickThumb = $this->createThumb($autoWidth, $autoHeight, $proportional);
                $thumbBase64 = base64_encode($imagickThumb->getImageBlob());
            }
            if (isset($options["include_base64_prefix"])) {
                $thumbBase64 = self::BASE64_IMAGE_PREFIX.$thumb;
            }
            return $thumbBase64;
        }
        return false;
    }


    /**
     * Prepare the media to be saved, setting meta data
     *
     * @return void
     */
    protected function prepareSave() {
        $this->storage_policy = self::STORAGE_POLICY_INDB;

        if($this->type === self::IMAGE_TYPE) {
            if (Config::get('media-uploader.storage_policy') === self::STORAGE_POLICY_FILESYSTEM) {
                $this->storage_policy = self::STORAGE_POLICY_FILESYSTEM;
            }

            $imagick  = $this->getOriginaImageImagick();
            $d = $imagick->getImageGeometry();

            $this->dimension_type = self::DIMENSION_TYPE_SIZED;
            $this->width = $d['width'];
            $this->height = $d['height'];
            $this->width_unit = self::PIXEL_UNIT;
            $this->height_unit = self::PIXEL_UNIT;
            $this->preview_image = null; // ad,for example,youtube preview image here
        } else {
            $this->dimension_type = self::DIMENSION_TYPE_RESPONSIVE;
        }
    }

    /**
     * Get the save path in writtable mode for a given folder
     *
     * @param string $mediaConfigFolder
     * @return string $savePath
     */
    protected function getSavePath($mediaConfigFolder) {
        $savePath = Config::get("media-uploader.$mediaConfigFolder");

        if(!File::exists($savePath)) {
            File::makeDirectory($savePath);
            $this->command->setWritePermission($savePath);
        }
        return $savePath;
    }

    /**
     * Get the thumbnail file name
     *
     * @param integer $width
     * @param integer $height
     * @return string $thumbFileName
     */
    protected function getThumbFileName($width, $height) {
        $dimensions = $width."_".$height;

        $thumbFileName = str_replace(".".$this->ext, self::THUMB_SUFFIX."$dimensions.".$this->ext, $this->unique_name);
        return $thumbFileName;
    }
}
