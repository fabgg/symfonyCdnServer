<?php
/**
 * Created by PhpStorm.
 * User: fabrice
 * Date: 20/01/2018
 * Time: 08:22
 */

namespace AppBundle\Services;


use AppBundle\Entity\Document;
use Doctrine\ORM\EntityManager;
use Imagine\Image\Box;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Imagine\Gd\Imagine;

class DocumentService
{

    protected $tmp_directory;
    protected $jukebox_directory;
    protected $jukebox_alias;
    protected $thumb_height;
    protected $thumb_width;
    protected $ffmpeg_path;
    protected $ffprobe_path;

    protected $em;
    protected $request;

    public function __construct(Container $container, EntityManager $entityManager, RequestStack $requestStack)
    {
        $this->em = $entityManager;
        $this->tmp_directory = $container->getParameter('tmp_directory');
        $this->jukebox_directory = $container->getParameter('jukebox_directory');
        $this->jukebox_alias = $container->getParameter('jukebox');
        $this->thumb_height = $container->getParameter('thumb_height');
        $this->thumb_width = $container->getParameter('thumb_width');
        $this->ffmpeg_path = $container->getParameter('ffmpeg_path');
        $this->ffprobe_path = $container->getParameter('ffprobe_path');

        $this->request = $requestStack->getCurrentRequest();
    }

    public function initDocument(Document $document){
        if(!$document->getId() && !$document->getFilePath()){
            $document->setFileName(base_convert(uniqid(dechex(rand(123456,987654))), 16, 36));
            $document->setFileTmpPath($this->tmp_directory);
        }
    }

    public function saveDocument(Document $document, $process = false){
        $this->moveToJukebox($document);
        $this->setDimensions($document);
        if($process) $this->generateThumb($document);
        $this->em->persist($document);
        $this->em->flush();
        return $this->getResponse($document);
    }

    public function deleteDocument(Document $document){
        $finalPath = $this->getAbsolutePath($document);

        unlink($finalPath.$document->getFileName());
        if($document->getFileThumb()) unlink($finalPath.$document->getFileThumb());
        if($document->getFilePreview()) unlink($finalPath.$document->getFilePreview());

        $this->em->remove($document);
        $this->em->flush();
        return array('result'=>'document deleted');
    }

    public function makeThumb(Document $document){
        //$this->generateCompositionThumb($document);
        return $this->getResponse($this->generateCompositionThumb($document));
    }

    public function getResponse(Document $document){
        $response = array(
            'id'        => $document->getId(),
            'mime'      => $document->getFileMime(),
            'type'      => $document->getFileType(),
            'url'       => $this->getWebUri($document),
            'width'     => $document->getWidth(),
            'height'    => $document->getHeight()
        );

        if($document->getFileThumb()) $response['thumb'] =  $this->getWebUri($document,true);
        if($document->getFilePreview()) $response['preview'] =  $this->getWebUri($document,false,true);

        return $response;
    }

    private function moveToJukebox(Document $document){
        if(!$document->getFileTmpPath() && !$document->getFileName()) throw  new Exception("document are not initilized");

        $temporaryResource = $document->getFileTmpPath().$document->getFileName();
        if (!$document->getFileExtension()){
            if (!$document->getFileMime()) {
                $document->setFileMime(mime_content_type($temporaryResource));
            }
            $this->getExtensionAndTypeFormMime($document);
            $document->setFileName($document->getFileName().'.'.$document->getFileExtension());
        }
        if(!$document->getFileSize()) $document->setFileSize(filesize($temporaryResource));

        $finalPath = $this->getAbsolutePath($document);
        if(!is_dir($finalPath)) mkdir($finalPath, 0755, true);
        $destinationResource = $finalPath.$document->getFileName();
        rename($temporaryResource,$destinationResource);

        if($document->getFileType() === 'video') $this->generatePreview($document);

    }

    private function getExtensionAndTypeFormMime(Document $document){
        $extension = $type = null;
        switch ($document->getFileMime()){
            case 'image/gif' :
                $extension = 'gif';
                $type = 'image';
                break;
            case 'image/jpeg' :
                $extension = 'jpg';
                $type = 'image';
                break;
            case 'image/png' :
                $extension = 'png';
                $type = 'image';
                break;
            case 'video/mp4' :
                $extension = 'mp4';
                $type = 'video';
                break;
        }
        if(!$extension){
            unlink($document->getFileTmpPath().$document->getFileName());
            throw new Exception("This file can't be manage by this server");
        }
        $document->setFileExtension($extension);
        $document->setFileType($type);
    }

    private function getAbsolutePath(Document $document){
        $path = $this->jukebox_directory;
        if(!$document->getFilePath()) $document->setFilePath($this->generateRandPath());
        foreach ($document->getFilePath() as $v) {
            $path .= $v . '/';
        }
        return $path;
    }

    private function  generateRandPath()
    {
        $randPath = array();
        for ($i = 0; $i <= 4; $i++) {
            $randPath[] = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }

        return $randPath;
    }

    private function getWebUri(Document $document, $thumb = false, $preview = false){
        if($thumb && !$document->getFileThumb()) return null;
        $uri = $this->request->getSchemeAndHttpHost().'/'.$this->jukebox_alias;
        if(!preg_match("/\/$/",$uri)) $uri .= '/';
        foreach ($document->getFilePath() as $v) {
            $uri .= $v . '/';
        }
        if($thumb) $uri .= $document->getFileThumb();
        elseif ($preview) $uri .= $document->getFilePreview();
        else $uri .= $document->getFileName();
        return $uri;
    }

    private function generateThumb(Document $document){

        $finalPath = $this->getAbsolutePath($document);
        $thumbName = ($document->getFileType()==='image') ? 'thumb_'.$document->getFileName() : 'thumb_'.$document->getFilePreview();
        $resource = ($document->getFileType()==='image') ? $finalPath.$document->getFileName(): $finalPath.$document->getFilePreview();
        $imagine = new Imagine();
        $image = $imagine->open($resource);
        if($document->getFileExtension() ==='gif' || $document->getFileType()==='video' ){
            $ratio = $image->getSize()->getWidth()/$image->getSize()->getHeight();
            $thumb_ratio = $this->thumb_width / $this->thumb_height;
            if($ratio>=$thumb_ratio) $this->thumb_height = round($this->thumb_height/$ratio);
            else $this->thumb_width = round($this->thumb_width/$ratio);
            $box = new Box($this->thumb_width,$this->thumb_height);
            $image->resize($box)->save($finalPath.$thumbName, array('flatten' => false));
        }
        else {
            $box = new Box($this->thumb_width,$this->thumb_height);
            $image->thumbnail($box)->save($finalPath.$thumbName);
        }

        $document->setFileThumb($thumbName);
    }

    private function generateCompositionThumb(Document $document){
        $width = 250;
        $height = 150;
        $finalPath = $this->getAbsolutePath($document);
        $thumbName = ($document->getFileType()==='image') ? $width.'_'.$height.'_'.$document->getFileName() : $width.'_'.$height.'_'.$document->getFilePreview();
        $resource = ($document->getFileType()==='image') ? $finalPath.$document->getFileName(): $finalPath.$document->getFilePreview();
        $imagine = new Imagine();
        $image = $imagine->open($resource);
        if($document->getFileExtension() ==='gif' || $document->getFileType()==='video' ){
            $ratio = $image->getSize()->getWidth()/$image->getSize()->getHeight();
            $thumb_ratio = $width / $height;
            if($ratio>=$thumb_ratio) $height = round($height/$ratio);
            else $width = round($$width/$ratio);
            $box = new Box($width,$height);
            $image->resize($box)->save($finalPath.$thumbName, array('flatten' => false));
        }
        else {
            $box = new Box($width,$height);
            $image->thumbnail($box)->save($finalPath.$thumbName);
        }

        $compositionThumb = new Document();
        $compositionThumb->setFileType($document->getFileType());
        $compositionThumb->setFilePath($document->getFilePath());
        $compositionThumb->setFileMime($document->getFileMime());
        $compositionThumb->setFileName($thumbName);
        $compositionThumb->setFileExtension($document->getFileExtension());
        $compositionThumb->setFileSize(filesize($finalPath.$thumbName));
        $this->em->persist($compositionThumb);
        $this->em->flush();

        return $compositionThumb;
    }

    private function generatePreview(Document $document){
        if($document->getFileType() !== 'video') return false;
        $finalPath = $this->getAbsolutePath($document);
        $elements =  explode('.', $document->getFileName());
        $previewName = 'preview_'.$elements[0].'.gif';
        $ffmpeg = \FFMpeg\FFMpeg::create(array(
            'ffmpeg.binaries'  => $this->ffmpeg_path,
            'ffprobe.binaries' => $this->ffprobe_path
        ));
        $video = $ffmpeg->open( $finalPath.$document->getFileName() );
        $video
            ->gif(\FFMpeg\Coordinate\TimeCode::fromSeconds(2), new \FFMpeg\Coordinate\Dimension(480, 360), 2)
            ->save($finalPath.$previewName);
        $document->setFilePreview($previewName);
    }

    private function setDimensions(Document $document){
        $finalPath = $this->getAbsolutePath($document);
        if($document->getFileType() === 'video'){
            $ffprobe = \FFMpeg\FFProbe::create(array('ffprobe.binaries' => $this->ffprobe_path));
            $video_dimensions = $ffprobe->streams( $finalPath.$document->getFileName() )->videos()->first()->getDimensions();
            if($video_dimensions){
                $document->setHeight(intval($video_dimensions->getHeight()));
                $document->setWidth(intval($video_dimensions->getWidth()));
            }
        }
        else if($document->getFileType() === 'image'){
            $imagine = new  Imagine();
            $image = $imagine->open($finalPath.$document->getFileName());
            if($image){
                $document->setHeight(intval($image->getSize()->getHeight()));
                $document->setWidth(intval($image->getSize()->getWidth()));
            }
        }
        return $document;
    }

}