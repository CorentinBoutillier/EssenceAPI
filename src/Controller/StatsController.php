<?php
/**
 * Created by PhpStorm.
 * User: corentinboutillier
 * Date: 07/09/2018
 * Time: 23:23
 */

namespace App\Controller;

use JsonSchema\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity;

class StatsController extends Controller
{
    /**
     * @Rest\View()
     * @Rest\Post("/api/1.0/essence/stats")
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $content = $request->getContent();
        $json_content = json_decode($content);

        $data = $em->getRepository(Entity\EssenceActivity::class)
            ->getStatsBetweenDates(new \DateTime($json_content->dateStart), new \DateTime($json_content->dateEnd));

        return new Response(json_encode($data));
    }



}