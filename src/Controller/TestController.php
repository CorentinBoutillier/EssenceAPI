<?php

namespace App\Controller;

use JsonSchema\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity;

class TestController extends Controller
{
    /**
     * @Rest\View()
     * @Rest\Post("/api/1.0/test")
     */
    public function index(Request $request)
    {
        $content = $request->getContent();
        $json_content = json_decode($content);

        // On regarde si le JSON est valide
        if (!is_string($content) || !is_array(json_decode($content, true)) || (json_last_error() != JSON_ERROR_NONE)) {
            return $this->json(array('error' => 'Invalid or malformed JSON', 'violations' => array()), 400);
        }

        $validator = new Validator();
        $validator->check($json_content, json_decode(file_get_contents(__DIR__ . '/../Model/Schema/test-schema.json')));
        if (!$validator->isValid()) {
            return $this->json(array('error' => 'JSON does not validate', 'violations' => $validator->getErrors()), 400);
        }
        return new Response(json_encode('Nice !'));
    }

    /**
     * @Rest\View()
     * @Rest\Post("/api/1.0/users")
     */
    public function getAllUsersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository(Entity\User::class)->createQueryBuilder('u')
            ->select('u.id')
            ->addSelect('u.username')
            ->addSelect('u.password')
            ->addSelect('u.lastName')
            ->addSelect('u.firstName')
            ->getQuery()
            ->getResult();

        $results = new \stdClass();
        $results->users = $users;

        return new Response(json_encode($users));
    }
}
