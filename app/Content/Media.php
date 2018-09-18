<?php

namespace App\Content;

use App\User;
use \Imagick;
use App\BaseModel;
use App\Util\DryPack;
use App\Content\Content;
use App\Content\Category;
use App\Content\MediaText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Media extends BaseModel
{
    /**
     * Constats that define values used in the class
     */
    public const STORAGE_POLICY_FILESYSTEM = "filesystem";
    public const STORAGE_POLICY_INDB = "indb";
    public const DIMENSION_TYPE_RESPONSIVE = "responsive";
    public const DIMENSION_TYPE_SIZED = "sized";
    public const THUMB_FORMAT = "jpg";
    public const VIDEO_TYPE = "video";
    public const EXTERNAL_VIDEO_TYPE = "external_video";
    public const HTML_TYPE = "html";
    public const IMAGE_TYPE = "image";
    public const AUDIO_TYPE = "audio";
    public const DOCUMENT_TYPE = "document";
    public const HTTP_PROTOCOL_START = "http:";
    public const THUMB_SUFFIX = "_thumb_";
    public const THUMB_PATH = "thumb_path";
    public const THUMB_TEMP_PATH = "thumb_path";
    public const UPLOAD_PATH = "upload_path";
    public const PIXEL_UNIT = "px";
    public const BASE64_IMAGE_PREFIX = "data:image/jpg;base64,";
    public const EXIF_PREFIX = "exif:*";
    public const EXIF_CAPTURE_DATE_KEY = "exif:datetimeoriginal";
    public const EXIF_CAPTURE_DEVICE_KEY = "exif:make";
    public const MEDIUM_SIZE_NAME = "medium";
    public const ORIGINAL_SIZE_NAME = "original";


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
    protected $fillable = [
        'file_name',
        'unique_name',
        'type',
        'status',
        'author_name',
        'owner_id',
        'mimetype',
        'ext',
        'length',
        'content',
        'thumb',
        'thumb_medium',
        'url',
        'owner_id',
        'storage_policy',
        'captured_at',
        'capture_device',
        'exif_data',
        'from',
        'external_content_id',
        'slug',
        'width',
        'height',
        'width_unit',
        'height_unit'
    ];

    /**
     * Transient imagick object representing the full image
     *
     * @var Imagick
     */
    protected $imagickOriginaImage = null;

    /**
     * Overwrite the based model to add the cast
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
       parent::__construct($attributes);
       $this->addCast(["exif_data"=> 'array']);
    }


    /**
    * Return the relationship with the author
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    /**
    * Return the relationship with the owner
    */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    /**
    * Return the relationship with the media texts
    */
    public function mediaTexts()
    {
        return $this->hasMany(MediaText::class);
    }

    /**
    * Return the categories relationship to which the media belongs
    * @return object
    */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_media', 'media_id', 'category_id');
    }

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
     * Resolve media file location
     *
     * @return void
     */
    public function resolveFileLocation() {
        // get youtube image based in video url
        if ($this->type === self::EXTERNAL_VIDEO_TYPE) {
            return $this->preview_image;
        } else {
            $savePath = $this->getSavePath(self::UPLOAD_PATH);
            $filePath = $savePath."/".$this->unique_name;
            return $filePath;
        }
    }

    /**
     * Prepare save, create thumb (if auto_thumb is true) and save in db
     *
     * @param array $options
     * @return void
     */
    public function save(array $options = []) {
        $this->prepareSave();
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
        if ($thumbBase64 === false) {
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

        switch ($this->type) {
            case self::IMAGE_TYPE:
                if($this->storage_policy === self::STORAGE_POLICY_INDB) {
                    return $this->content;
                } else {
                    $imageUri = $this->resolveFileLocation();
                    $blobContent = file_get_contents($imageUri);
                    $base64Content = base64_encode($blobContent);
                    return $includePrefix? self::BASE64_IMAGE_PREFIX.$base64Content : $base64Content;
                }
                break;
            case self::VIDEO_TYPE:
                if($this->preview_image !== null) {
                    if(DryPack::startsWith($this->preview_image, self::HTTP_PROTOCOL_START)) {
                        $blobContent = file_get_contents($this->preview_image);
                        $base64Content = base64_encode($blobContent);
                        return $includePrefix? self::BASE64_IMAGE_PREFIX.$base64Content : $base64Content;
                    } else {
                        return $includePrefix? self::BASE64_IMAGE_PREFIX.$this->preview_image : $this->preview_image;
                    }
                }
            case self::EXTERNAL_VIDEO_TYPE:
                $imageUrl = $this->resolveFileLocation();
                if($imageUrl) {
                    $blobContent = file_get_contents($imageUrl);
                    $base64Content = base64_encode($blobContent);
                    return $includePrefix? self::BASE64_IMAGE_PREFIX.$base64Content : $base64Content;
                }
            default:
                return null;
        }
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
        if ($this->type === self::IMAGE_TYPE || $this->type === self::EXTERNAL_VIDEO_TYPE) {
            // small thumb
            $thumbWidth = Config::get('media-uploader.auto_thumb_width');
            $thumbHeight = Config::get('media-uploader.auto_thumb_height');
            $thumbProportional = Config::get('media-uploader.auto_thumb_proportional');
            $imagickThumb = $this->createThumb($thumbWidth, $thumbHeight, $thumbProportional);
            $this->thumb = base64_encode($imagickThumb->getImageBlob());

            // medium thumb
            $mediumThumbWidth = Config::get('media-uploader.auto_medium_thumb_width');
            $mediumThumbHeight = Config::get('media-uploader.auto_medium_thumb_height');
            $thumbProportional = Config::get('media-uploader.auto_thumb_proportional');
            $imagickThumb = $this->createThumb($mediumThumbWidth, $mediumThumbHeight, $thumbProportional);
            $this->thumb_medium = base64_encode($imagickThumb->getImageBlob());
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
        if ($this->type === self::IMAGE_TYPE || $this->type === self::EXTERNAL_VIDEO_TYPE) {
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
        $autoMediumWidth = Config::get('media-uploader.auto_medium_thumb_width');
        $autoMediumHeight = Config::get('media-uploader.auto_medium_thumb_height');

        // parse thumb options
        $width = isset($options["width"]) ? $options["width"] : false;
        $height = isset($options["height"])? $options["height"] : false;
        $proportional = isset($options["proportional"]) ? $options["proportional"] : false;


        /**
         * If the width and height were not informed, or they are the same of the auto thumb, get the auto thumb
         */
        if($width !== false && $height !== false) {
            // get default thumbs
            if ($width === $autoWidth && $height === $autoHeight)  {
                $thumbBase64 = $this->thumb;
            } elseif ($width === $autoMediumWidth && $height === $autoMediumHeight) {
                $thumbBase64 = $this->thumb_medium;
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

        if (($this->type === self::IMAGE_TYPE || self::EXTERNAL_VIDEO_TYPE) && Config::get('media-uploader.auto_thumb') === true) {
            $this->createAutoThumb();
        }

        // default storage policy
        $this->storage_policy = self::STORAGE_POLICY_INDB;

        if($this->type !== self::EXTERNAL_VIDEO_TYPE && $this->type !== self::HTML_TYPE ) {
            $fileName = str_replace(".$this->ext", "", $this->file_name);
            $this->slug = DryPack::getSlug($fileName);
        }

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
            $this->preview_image = null; // when it is IMAGE_TYPE, it has no preview

            /* Get the EXIF information */
            $exifArray = $imagick->getImageProperties(self::EXIF_PREFIX);
            $this->exif_data = json_encode($exifArray);

            // Loop through the EXIF data
            foreach ($exifArray as $exifName => $exifValue)
            {
                if ( strtolower($exifName) === self::EXIF_CAPTURE_DATE_KEY && !isset($this->captured_at)) {
                    $this->captured_at = $exifValue;
                }
                if ( strtolower($exifName) === self::EXIF_CAPTURE_DEVICE_KEY && !isset($this->capture_device)) {
                    $this->capture_device = $exifValue;
                }
            }

            // Parse captured_at date, if present
            if($this->captured_at) {
                $this->captured_at = DryPack::parseDate($this->captured_at);
            }

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

    /**
     * Format/adjust the relations serialized data, transforming translations index array to locale key array
     *
     * @return array
     */
    public function relationsToArray() {
        $data = parent::relationsToArray();

        if(!isset($data["media_texts"])) {
            $data["media_texts"] = $this->mediaTexts;
        }
        if(!isset($data["categories"])) {
            $data["categories"] = $this->categories;
        }
        if(!isset($data["owner"])) {
            $data["owner"] = $this->owner;
        }
        if(isset($data["media_texts"])) {
            $mediaTexts = $data["media_texts"];
            $data["media_texts"] = [];
            $data["locales"] = [];
            foreach ($mediaTexts as $mediaText) {
                $data["media_texts"][$mediaText["locale"]] = $mediaText;
                $data["locales"][] = $mediaText["locale"];
            }
        }

        return $data;
    }

    /**
     * Format/adjust the relations serialized data, transforming translations index array to locale key array
     *
     * @return array
     */
    public function toArray() {
        $data = parent::toArray();
        $id = $data["id"];

        if (!isset($data["url"])) {
            if ($data["type"] !== self::EXTERNAL_VIDEO_TYPE) {
                $slug = $data["slug"];
                $data["url"] = "/".request()->route()->getPrefix(). "/medias/$id/content/$slug";
                if ($data["type"] === self::IMAGE_TYPE) {
                    $data["url_medium"] = "/".request()->route()->getPrefix(). "/medias/$id/content/$slug/thumb/medium";
                }
            }
        }

        return $data;
    }
}
