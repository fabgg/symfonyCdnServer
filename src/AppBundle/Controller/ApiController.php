<?php
/**
 * Created by PhpStorm.
 * User: fabrice
 * Date: 19/01/2018
 * Time: 23:07
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Document;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use GuzzleHttp\Client as Guzzle;


class ApiController extends Controller
{

    /**
     * @Post("/api/crawl")
     */
    public function getCrawlAction(Request $request){
        $authService = $this->get('app.service.auth');
        if (!$authService->validate()) throw new AccessDeniedException();

        $response = new JsonResponse();
        if(!$request->get('url')) $response->setData(array('error'=>'url parameter is missing'));

        $document = new Document();
        $docService = $this->get('app.service.document');
        $docService->initDocument($document);

        $guzzle = new Guzzle();

        $temporaryResource = fopen($document->getFileTmpPath().$document->getFileName(),'w');

        $res = $guzzle->request('GET', $request->get('url'), ['sink' => $temporaryResource,'http_errors' => false]);
        if($res->getStatusCode() != 200 ){
            return $response->setData(array('error'=>'code '.$res->getStatusCode().' for url '.$request->get('url')));
        }

        return $response->setData($docService->saveDocument($document,($request->get('process'))));

    }

    /**
     * @Get("/api/images")
     */
    public function getImagesAction(Request $request){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();

        $response = array();
        $docServices = $this->get('app.service.document');
        $documents_em = $this->getDoctrine()->getManager()->getRepository('AppBundle:Document')->findAll();
        foreach ($documents_em as $document){
            $response[] = $docServices->getResponse($document);
        }

        return new JsonResponse($response);
    }

    /**
     * @Get("/api/image/{id}")
     */
    public function getImageAction(Request $request, $id){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();

        $document = $this->getDoctrine()->getManager()->getRepository('AppBundle:Document')->find($id);
        if(!$document) throw  new NotFoundHttpException("can't find this file");

        $docServices = $this->get('app.service.document');
        return new JsonResponse($docServices->getResponse($document));
    }

    /**
     * @Delete("/api/image/{id}")
     */
    public function deleteImageAction(Request $request, $id){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();

        $document = $this->getDoctrine()->getManager()->getRepository('AppBundle:Document')->find($id);
        if(!$document) throw  new NotFoundHttpException("can't find this file");

        $docServices = $this->get('app.service.document');

        return new JsonResponse($docServices->deleteDocument($document));
    }

    /**
     * @Post("/api/save/b64")
     */
    public function postSaveB64Action(Request $request){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();

        $response = new JsonResponse();
        if(!$request->get('base64')) $response->setData(array('error'=>'base64 parameter is missing'));

        $document = new Document();
        $docService = $this->get('app.service.document');
        $docService->initDocument($document);

        file_put_contents ('/var/www/cdn_server/dev/var/tmp'.time().'.txt', $request->get('base64'));

        $base64Data = (preg_match('/data:([^;]*);base64,(.*)/', $request->get('base64'), $matches)) ? $matches[2]: $request->get('base64');
        file_put_contents ($document->getFileTmpPath().$document->getFileName(), base64_decode($base64Data));

        return $response->setData($docService->saveDocument($document,($request->get('process'))));
    }


    /**
     * @Post("/api/make/thumb")
     */
    public function postMakeThumbAction(Request $request){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();
        $response = new JsonResponse();
        if(!$request->get('id')) $response->setData(array('error'=>'id parameter is missing'));

        $document = $this->getDoctrine()->getManager()->getRepository('AppBundle:Document')->find($request->get('id'));
        if(!$document) throw  new NotFoundHttpException("can't find this file");
        $docServices = $this->get('app.service.document');
        return $response->setData($docServices->makeThumb($document));
    }

    /**
     * @Post("/api/composition/new/background")
     * json {source_type:id/url,source_value:string}
     */
    public function compositionNewBackgroundAction(Request $request){
        $authService = $this->get('app.service.auth');
        if(!$authService->validate()) throw new AccessDeniedException();
        $response = new JsonResponse();
        if(!$request->get('source_type') && !$request->get('source_value')) $response->setData(array('error'=>'source parameter is missing'));

        $copy = $request->get('copy');
        $source_value = $request->get('source_value');
        $source_type = $request->get('source_value');
        $docService = $this->get('app.service.document');

        if($source_type == "url"){
            $document = new Document();
            $docService->initDocument($document);
            $temporaryResource = fopen($document->getFileTmpPath().$document->getFileName(),'w');
            $guzzle = new Guzzle();
            $res = $guzzle->request('GET', $source_value, ['sink' => $temporaryResource,'http_errors' => false]);
            if($res->getStatusCode() != 200 ){
                return $response->setData(array('error'=>'code '.$res->getStatusCode().' for url '.$source_value));
            }

            return $response->setData($docService->saveCompositionBackgroundDocument($document));
        }
        elseif($source_type == "id") {
            $document_source = $$this->getDoctrine()->getManager()->getRepository('AppBundle:Document')->find($source_value);
            if(!($document_source instanceof Document)) throw new NotFoundHttpException('Document with id '.$source_value.' not found in db');
            $file_source = $docService->getAbsolutePath($document_source).$document_source->getFileName();
            if(!is_file($file_source)) throw new NotFoundHttpException('Document with id '.$source_value.' not found in filesystem');

            $document = new Document();
            $docService->initDocument($document);
            $file_destination = $docService->getAbsolutePath($document).$document->getFileName();
            if(copy($file_source, $file_destination))  return $response->setData($docService->saveCompositionBackgroundDocument($document));
            else throw new NotFoundHttpException('Copy failed');
        }

        else throw new NotFoundHttpException('not understand');
    }

}